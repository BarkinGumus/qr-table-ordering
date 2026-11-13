<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('label', 30);                 // what staff call it: "B4", "Teras 2"
            $table->string('area', 50)->nullable();
            $table->unsignedTinyInteger('capacity')->nullable();
            $table->char('qr_token', 22)->unique();      // 128 random bits; the only thing in the QR link
            $table->timestamp('qr_rotated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['restaurant_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_tables');
    }
};
