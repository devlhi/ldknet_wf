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
        if (Schema::hasTable('payment_method')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('payment_method', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->string('no_rekening', 100);
            $table->string('atas_nama', 200);
            $table->text('note');
            $table->string('category', 50);
            $table->text('service');
            $table->string('provider', 50)->nullable();
            $table->string('provider_code', 50)->nullable();
            $table->string('status', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method');
    }
};
