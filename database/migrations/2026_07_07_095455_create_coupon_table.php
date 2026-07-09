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
        if (Schema::hasTable('coupon')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('coupon', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('code');
            $table->integer('rate');
            $table->enum('otp', ['yes', 'no', 'ya', 'tidak']);
            $table->enum('status', ['Active', 'Not Active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon');
    }
};
