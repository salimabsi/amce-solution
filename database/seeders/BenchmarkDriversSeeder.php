<?php

namespace Database\Seeders;

use Domain\Driver\Contracts\DriverLocationStoreContract;
use Domain\Driver\Models\Entities\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

/**
 * Heavy-traffic seeder used for the driver geo-filter benchmark.
 *
 *   php artisan db:seed --class=BenchmarkDriversSeeder
 *
 * Wipes drivers + driver_locations + the Redis GEO set, then bulk-inserts
 * 50,000 available drivers with locations scattered around Riyadh
 * (lat 24.40-25.00, lng 46.40-47.00).
 */
class BenchmarkDriversSeeder extends Seeder
{
    private const TOTAL = 10_000;

    private const CHUNK = 2_000;

    public function run(): void
    {
        $this->command->info('Wiping drivers, locations, vehicles, bench users, and Redis GEO set...');
        DB::table('driver_locations')->truncate();
        DB::statement('TRUNCATE TABLE drivers RESTART IDENTITY CASCADE');
        DB::table('vehicles')->truncate();
        DB::table('users')->where('email', 'like', 'bench-driver-%')->delete();
        Redis::del('drivers:locations');

        $vehicleId = Vehicle::factory()->create()->id;
        $passwordHash = Hash::make('password');

        $this->command->info('Bulk-inserting '.number_format(self::TOTAL).' drivers + locations...');
        $bar = $this->command->getOutput()->createProgressBar(self::TOTAL / self::CHUNK);
        $now = now();

        for ($batch = 0; $batch < self::TOTAL / self::CHUNK; $batch++) {
            // 1) Bulk insert users RETURNING id
            $userRows = [];
            for ($i = 0; $i < self::CHUNK; $i++) {
                $idx = $batch * self::CHUNK + $i;
                $userRows[] = [
                    'name' => "Bench Driver $idx",
                    'email' => "bench-driver-$idx@example.com",
                    'password' => $passwordHash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $userIds = array_column($this->bulkInsertReturning('users', $userRows), 'id');

            // 2) Bulk insert drivers RETURNING id
            $driverRows = array_map(fn ($uid) => [
                'user_id' => $uid,
                'is_available' => true,
                'vehicle_id' => $vehicleId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $userIds);
            $driverIds = array_column($this->bulkInsertReturning('drivers', $driverRows), 'id');

            // 3) Bulk insert driver_locations
            $locationRows = array_map(fn ($did) => [
                'driver_id' => $did,
                'lat' => 24.40 + mt_rand(0, 60000) / 100000,
                'lng' => 46.40 + mt_rand(0, 60000) / 100000,
                'created_at' => $now,
                'updated_at' => $now,
            ], $driverIds);
            DB::table('driver_locations')->insert($locationRows);

            $bar->advance();
        }
        $bar->finish();
        $this->command->newLine();

        $this->command->info('Pushing '.number_format(self::TOTAL).' locations into Redis GEO set...');
        $store = app(DriverLocationStoreContract::class);
        DB::table('driver_locations')
            ->select('driver_id', 'lat', 'lng')
            ->orderBy('driver_id')
            ->chunk(5000, function ($rows) use ($store) {
                foreach ($rows as $row) {
                    $store->set((int) $row->driver_id, (float) $row->lat, (float) $row->lng);
                }
            });

        $this->command->info('Done. '.number_format(self::TOTAL).' available drivers.');
    }

    /** @param  array<int, array<string, mixed>>  $rows  @return array<int, object> */
    private function bulkInsertReturning(string $table, array $rows): array
    {
        $columns = array_keys($rows[0]);
        $rowPlaceholder = '('.implode(', ', array_fill(0, count($columns), '?')).')';
        $valuesSql = implode(', ', array_fill(0, count($rows), $rowPlaceholder));
        $bindings = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $bindings[] = $row[$col];
            }
        }
        $sql = 'INSERT INTO '.$table.' ('.implode(', ', $columns).') VALUES '.$valuesSql.' RETURNING id';

        return DB::select($sql, $bindings);
    }
}
