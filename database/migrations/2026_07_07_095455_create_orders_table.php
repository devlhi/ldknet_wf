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
        if (Schema::hasTable('orders')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('idpel', 50);
            $table->string('email');
            $table->string('nama', 100);
            $table->string('paket', 100);
            $table->string('alamat');
            $table->string('nomor', 16);
            $table->enum('status', ['Active', 'Isolir', 'Berhenti']);
            $table->date('date');
            $table->date('expdate');
            $table->text('pppoe_user');
            $table->string('id_router', 15);
            $table->string('mode', 15);
            $table->string('nama_odp', 15);
            $table->string('port_odp', 15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
