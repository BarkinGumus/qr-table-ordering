<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dining_table_id')->constrained()->cascadeOnDelete();
            // Equals dining_table_id while the session is pending or open,
            // NULL afterwards. The unique index then guarantees at most one
            // live session per table, even if two phones scan at once.
            $table->unsignedBigInteger('live_table_id')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->boolean('ordering_blocked')->default(false);
            $table->timestamp('opened_at')->useCurrent();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });

        // One row per phone that joined a session. Holds only a hash of the
        // random key kept in the phone's cookie; nothing personal.
        Schema::create('session_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_session_id')->constrained()->cascadeOnDelete();
            $table->char('key_hash', 64)->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_guests');
        Schema::dropIfExists('table_sessions');
    }
};
