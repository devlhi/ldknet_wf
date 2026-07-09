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
        if (Schema::hasTable('template_message')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('template_message', function (Blueprint $table) {
            $table->integer('id', true);
            $table->mediumText('notif_tagihan');
            $table->mediumText('notif_pengingat');
            $table->mediumText('notif_tagihan_terbayar');
            $table->text('notif_pelanggan_baru');
            $table->text('notif_tagihan_email');
            $table->text('notif_pengingat_email');
            $table->text('notif_tagihan_terbayar_email');
            $table->text('notif_pelanggan_baru_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_message');
    }
};
