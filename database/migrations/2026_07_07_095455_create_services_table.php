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
        if (Schema::hasTable('services')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('services', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('paket', 200);
            $table->string('ppp_profile', 500);
            $table->float('harga');
            $table->float('ppn');
            $table->enum('status', ['Tersedia', 'Tidak Tersedia']);
            $table->string('mode', 15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
