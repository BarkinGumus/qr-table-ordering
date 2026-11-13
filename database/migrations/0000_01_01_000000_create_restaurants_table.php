<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 80)->unique();
            $table->string('default_locale', 5)->default('tr');
            $table->json('locales');
            $table->char('currency', 3)->default('TRY');
            $table->string('timezone', 64)->default('Europe/Istanbul');
            // A waiter must confirm the first order of a new table session.
            $table->boolean('confirm_first_order')->default(true);
            $table->boolean('ordering_enabled')->default(true);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
