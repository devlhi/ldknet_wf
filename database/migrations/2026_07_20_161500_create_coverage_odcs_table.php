<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coverage_odcs')) {
            return;
        }

        Schema::create('coverage_odcs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->string('code', 100)->nullable()->unique();
            $table->string('latitude', 50);
            $table->string('longitude', 50);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_odcs');
    }
};
