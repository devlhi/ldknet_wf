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
        if (Schema::hasTable('whatsapp_setting')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('whatsapp_setting', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nama');
            $table->string('api_url');
            $table->string('api_key');
            $table->string('sender', 15);
            $table->enum('mode', ['on', 'off']);
            $table->enum('type', ['blast', 'otp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_setting');
    }
};
