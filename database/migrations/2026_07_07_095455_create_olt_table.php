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
        if (Schema::hasTable('olt')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('olt', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nama');
            $table->mediumText('ip');
            $table->mediumText('username');
            $table->mediumText('password');
            $table->mediumText('cookies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olt');
    }
};
