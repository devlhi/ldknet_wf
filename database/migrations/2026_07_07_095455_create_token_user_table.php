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
        if (Schema::hasTable('token_user')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('token_user', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('token');
            $table->string('email', 50);
            $table->integer('date_create');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_user');
    }
};
