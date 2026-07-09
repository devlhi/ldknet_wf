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
        if (Schema::hasTable('report')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('report', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('category');
            $table->enum('jenis_kategori', ['Pemasukan', 'Pengeluaran']);
            $table->integer('balance');
            $table->string('asal');
            $table->dateTime('date');
            $table->string('image', 500);
            $table->string('account', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report');
    }
};
