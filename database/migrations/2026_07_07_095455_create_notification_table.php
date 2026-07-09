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
        if (Schema::hasTable('notification')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('notification', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('sebelum');
            $table->enum('notif_tagihan', ['on', 'off']);
            $table->enum('notif_jatuh_tempo_h', ['on', 'off']);
            $table->enum('notif_jatuh_tempo_h1', ['on', 'off']);
            $table->enum('notif_jatuh_tempo_h3', ['on', 'off']);
            $table->enum('notif_jatuh_tempo_h7', ['on', 'off']);
            $table->enum('notif_pelanggan_baru', ['on', 'off']);
            $table->enum('notif_tagihan_terbayar', ['on', 'off']);
            $table->enum('notif_pembelian_voucher', ['on', 'off']);
            $table->enum('notif_pembelian_voucher_terbayar', ['on', 'off']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
