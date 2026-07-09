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
        if (Schema::hasTable('router')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('router', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nama', 50);
            $table->text('dns');
            $table->text('ip');
            $table->string('username', 100);
            $table->string('password', 100);
            $table->string('interface', 10);
            $table->enum('status', ['Active', 'Not Active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router');
    }
};
