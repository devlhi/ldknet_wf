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
        if (Schema::hasTable('note')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('note', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('message');
            $table->dateTime('date');
            $table->string('image', 500);
            $table->string('account', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note');
    }
};
