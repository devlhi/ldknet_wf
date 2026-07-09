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
        if (Schema::hasTable('psb')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('psb', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('idpel');
            $table->mediumText('package');
            $table->mediumText('price');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psb');
    }
};
