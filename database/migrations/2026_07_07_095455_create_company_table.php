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
        if (Schema::hasTable('company')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('company', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('email');
            $table->text('phone_number');
            $table->string('address', 200);
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('country', 100);
            $table->string('postal_code', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company');
    }
};
