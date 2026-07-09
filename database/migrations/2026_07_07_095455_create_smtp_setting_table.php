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
        if (Schema::hasTable('smtp_setting')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('smtp_setting', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('key');
            $table->text('nama');
            $table->string('email', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_setting');
    }
};
