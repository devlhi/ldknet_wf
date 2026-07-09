<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('pool')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('pool', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('pool_host', 200);
            $table->string('pool_range', 200);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool');
    }
};
