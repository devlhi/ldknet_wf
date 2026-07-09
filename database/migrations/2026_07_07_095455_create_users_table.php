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
        if (Schema::hasTable('users')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('users', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email', 40);
            $table->string('nama', 50);
            $table->string('nomor', 15);
            $table->mediumText('password');
            $table->integer('balance');
            $table->enum('level', ['admin', 'user', 'member', 'reseller', 'developer', 'cs', 'finance', 'technician']);
            $table->integer('verify_account');
            $table->enum('status_account', ['Active', 'Non Active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
