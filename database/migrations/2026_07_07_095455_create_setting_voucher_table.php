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
        if (Schema::hasTable('setting_voucher')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('setting_voucher', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('server', 200);
            $table->string('lenght', 200);
            $table->string('karakter', 200);
            $table->text('template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_voucher');
    }
};
