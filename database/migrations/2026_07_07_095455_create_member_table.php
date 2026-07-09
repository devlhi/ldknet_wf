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
        if (Schema::hasTable('member')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('member', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('username');
            $table->string('nomor', 16);
            $table->enum('status', ['Active', 'Isolir', 'Berhenti']);
            $table->date('date');
            $table->date('expdate');
            $table->string('id_router', 15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member');
    }
};
