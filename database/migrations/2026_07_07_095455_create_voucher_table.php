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
        if (Schema::hasTable('voucher')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('voucher', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nama', 100);
            $table->string('harga', 100);
            $table->string('komisi', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};
