<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dining_table_id')->constrained()->cascadeOnDelete();
            // NULL when a waiter enters the order for the table.
            $table->foreignId('session_guest_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('number');           // short per-restaurant number for humans
            $table->string('status', 30);
            $table->string('note', 300)->nullable();
            $table->uuid('idempotency_key');             // sent by the phone; stops double submits
            $table->decimal('total', 10, 2);
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['restaurant_id', 'idempotency_key']);
            $table->unique(['restaurant_id', 'number']);
            $table->index(['restaurant_id', 'updated_at']);   // staff screens ask "what changed since..."
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name', 150);         // copied at order time
            $table->decimal('unit_price', 10, 2);        // copied at order time
            $table->unsignedSmallInteger('quantity');
            $table->string('note', 200)->nullable();
            $table->string('station', 20);
            $table->string('status', 20)->default('received');
            $table->timestamp('status_changed_at')->nullable();
            $table->string('cancel_reason', 200)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'station', 'status']);   // kitchen and bar boards
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_session_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 10, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
