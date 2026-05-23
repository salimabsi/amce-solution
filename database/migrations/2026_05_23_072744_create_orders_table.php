<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'assigned', 'being_served', 'cancelled', 'completed'])->default('pending');
            $table->enum('type', ['standard', 'fragile', 'refrigerated', 'hazardous']);
            $table->enum('priority', ['normal', 'vip'])->default('normal');
            $table->decimal('weight_kg', 8, 2);
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->decimal('dropoff_lat', 10, 7);
            $table->decimal('dropoff_lng', 10, 7);
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'status', 'created_at']);
        });

        // Partial index: only pending orders — avoids full table scan on assignment queries
        DB::statement("CREATE INDEX orders_pending_partial_idx ON orders (id) WHERE status = 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
