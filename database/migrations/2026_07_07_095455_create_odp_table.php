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
        if (Schema::hasTable('odp')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('odp', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nama');
            $table->string('port');
            $table->string('latitude');
            $table->string('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odp');
    }
};
