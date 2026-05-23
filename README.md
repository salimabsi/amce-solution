
### Selecting the best driver for an order

> The current problem:
> We have millions of orders, and reading the ones that wait for assignment to display in the operations team page takes too much time.

This is a almost a DS-like problem, I would solve it in 2 steps:
#### Step 1: DB level

The typical db design would be having something like a `status` column in the orders table of values:
- `pending`: The order is not assigned to a driver yet.
- `assigned`: Assigned but not yet served.
- `being_served`: The driver currently is serving it.
- `cancelled`: The order is cancelled at any given time (business decision)
- `completed`: The order has been served successfully

Even though having an index on `status` column is good because of the *low cardinality* and the *frequent querying* , but this index only solves the problem of querying the records based on statuses *collectively*, and doesn't solve the problem of frequent querying of the desired orders (orders that wait for assignment).

In this case, we only care about one thing: Can the order be assigned or not ?

There are 2 ways to solve it:
1. Creating a partial index: it contains only the subset of records we care about (where status is `pending`), therefore, no full table scan. Only PostgreSQL supports Partial index.
2. Null trick: Introducing a high-level `assignable` column of values: `1` or `null`, where the records of status `assigned`, `being_served`, `cancelled`, and `completed` would be having `assignable: null` , and then creating an index on it, the index will ignore records of `null` values, therefore the index will include the desired subset as the partial index solution. MySQL supports this.

This will make querying the orders for the operations team a lot faster.

#### Step 2: Using Redis:

While the above solution reduces the query time by X amount of time (Clearly obvious since scanning 5% subset of DB is faster than scanning full DB)

A Redis server can be very beneficial for many reasons:

let's say a new order comes in, the model would be
- One write to the DB for persistence (Source of Truth) -> we cannot rely on redis
- One write to the pending sorted set "ZSET" in redis:
	- The main benefit of storing the pending orders in redis regardless of underlying DS is the builtin GEO index which helps with the direct long/lat/distance filtering at redis-level when choosing the best driver later in the assign action.
	- ZSET (Sorted Set): orders will stored **SORTED** by score:
		- By timestamp as a score: stored as they come in -> Makes admin pagination faster
		- Or by business score like VIP rank of companies used to deal with winch -> The operations team might like it to prioritize assignment  "business needs maybe?"




Slow Read solution:
Using Redis for fast read + bulk insert out of redis on a partial index for DB "Mixing"

How ?
- Only the operations team reads the pending orders, so using Redis for the instant reading is **obviously** faster and less complex than having a separate DB table for the pending orders


Write Pressure: (Huge amount of orders):
- Clearly INSERT for each order is less performant and causes table lock -> high latency and pressure.

The solution:
- Each new order go to redis stream (waiting list of orders), not to DB directly via INSERT
- A background worker runs at a configured way (pull X orders for each X seconds or milliseconds) and then **Bulk INSERT** in the orders table

So we would be having 2 DS in Redis for orders as they come in.

1. ZSET list: for fast read for operations team
	- Storing only the attributes that affect the assignment action
2. Stream: to absorb the massive individual write of orders and to perform Bulk INSERT
	- Storing the whole record as the source of truth.

##### A new problem comes in:
writing to both (DB + Redis) is not atomic, if one fails the, other might succeeds :)

##### The solution:
A new DB table, its only purpose is the (source of truth): `unprocessed_orders` or similar name
no need for table optimization, it will be small and cleaned up quickly

The flow:
1. order comes in
2. stored directly in this table as a raw payload with
	- `processed_at` null = unprocessed
- A worker running in the background: 
	- read unprocessed rows
	- then bulk INSERT in the orders table
	- then push to ZSET redis
	- then delete rows with processed_at is set

And we don't need the redis stream anymore

**With this final solution, WE ARE SURE**, that we have the order stored in DB for persistence, and pushed to redis for fast read
The only tradeoff is that, the order will delayed a bit until it is visible in the operations team assign page: ACCEPTABLE for the sake of keeping the data safe


--- 

Now comes the problem of assigning the most driver fit for a given order. Assuming `/orders/{id}/assign` is the action made by the operations team.

#### Driver Location
Before this, the driver live location is needed:
A proper solution would be: An update request for long/lat is stored in 2 places:
- Redis: for fast query and to use the GEO index filtration
- DB (as a source of truth)
	- A driver might have tens of columns, updating 2 fields long/lat causes DB lock/pressure
	- Separating driver location into a new table driver_locations is better, will be hot :)

No need for short period updates, 1 minute or 30 seconds is enough. HTTP requests for push/poll are fine -> needs to be monitored.

Now that we have the orders with their attributes that affect the assignment, all stored in redis

### The Algorithm:

Inspired by the internal design of Kubernetes Scheduler:
##### 1. Filtering Phase:
Clearly not all drivers ***CAN*** take the order
- Hard Constrains (Filtration before Scoring)
	- Driver availability
	- Driver distance "GEO Index" from the pickup location "Can move to soft constrains depending on requirements"
	- Vehicle Type
	- ... etc

Mix of specs from Driver/Vehicle and Order "Business Reqs"

Some of the specs are used for filtration in Redis, and some in the memory (PHP Level).

Now we have the feasible drivers (drivers that can take the order), but we need the BEST fit

Several combination of factors contribute in the best FIT, those include but not limited to:
- Closest driver "Distance"
- Fitness of Vehicle Capacity (10% of empty space is better than 40% empty space)
- Order Weight (Using a Truck to move 10KG doesn't make any sense)
- ... etc (A lot of several factors can be used in the scoring)

##### Running the Scoring algorithm:

The objects that run through the scoring algorithm **must include all the specs required** (Mix of Driver/Vehicle/Order)

<img width="1727" height="2315" alt="winch" src="https://github.com/user-attachments/assets/f98e11d8-92a5-4cfa-b89f-471a27869a3a" />

Since this algorithm runs:
1. Over a few **feasible** drivers with their vehicles "maybe tens"
2. For a few factors "maybe tens"
3. Upon the operations team "Assign" action "Not system level"
4. Considering PHP doesn't support concurrency natively

Since all of that, we conclude that running it sequentially "not concurrently" is good and is not worth looking for other external solution


#### Problem:
The assign action is only for operations team, if multiple person try to perform the assign action for the same order, a race condition might occur


2 HTTP requests come in
- 2 commands are pushed to redis engine -> but redis is single threaded (will only process one command at a time) -> first command will update, the second will update nothing
- 2 DB inserts trying to update the status to `being_served` or to `assignable` to null
	- first insert -> rows affect  = 1, the second insert -> rows affect = 0

If business allows that, A proper solution:
- Check on the number of db rows affected, if 0 -> then it is already assigned -> throw error.

---

#### Follow-up

For the requirement of getting orders of a specific driver  `/drivers/{id}/orders` 

I assume the orders needed are the ones that have been assigned regardless of the `status`

Since this would QUERY all the orders from the history "ALL ORDERS", and the orders will be fetched by the `driver_id`, an optimization would be **creating an index** on
- `driver_id` (for driver indexing)
- and `status` (saves us from system filtration for admin)
- and `created_at` (for having them pre-sorted)
with default pagination implemented at query level "in system code".

This is for the orders from history "PAST ONES"

For active orders "Currently in Serve", This QUERY would be affected by how/where we choose to store the active orders and update them for live tracking, Out of Assignment Scope :)

But I would take the advantage and provide some thoughts

--- 

### Active orders and Live Tracking

Since PHP doesn't support concurrency natively, and each request comes to the server take a thread from the thread pool "managed by PHP process manager", and since each thread lifecycle costs a lot of overhead. Since all of that, in our decision we would take in consideration these factors: 
- Do we expect our system to have a few active orders (trips)
	- Yes: Normal push/pull requests (HTTP) are fine, "Needs to be monitored", considering these requests are normal ones like the others.
	- No: Clearly PHP server cannot handle them

Solution: Using Websocket (Open Connections):

Reason:
Although it is a persistent open connection, the cost of bidirectional updates are cheaper than HTTP push/poll, since the connection would be open for once and there is no HTTP overhead (parsing, middleware, ...etc)

First thing come to my mind is using Nodejs because of its **Event Loop** nature:
- One thread handles all open connections
- This Single thread watches for all connections, when any update is received, it handles the update then leave the connection idle.
- No per-connection thread.

There is also a PHP runtime implementation for Event Loop "", which is preferred over Nodejs, to keep our systems all PHP

If the system is expecting high throughputs and open connections with heavy CPU,

Then Event Loop nature won't help, since the single thread will block the other connections,
A proper solution will be Go because it supports concurrently natively, threads (goroutines) are managed by runtime not OS-level (cheaper).

The Judgement is Metrics/Numbers driven :)
