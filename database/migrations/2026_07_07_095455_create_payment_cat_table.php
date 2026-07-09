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
        if (Schema::hasTable('payment_cat')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('payment_cat', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->string('category', 50);
            $table->string('status', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_cat');
    }
};
