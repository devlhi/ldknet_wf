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
        if (Schema::hasTable('website')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('website', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title', 100);
            $table->string('logo', 200);
            $table->string('logo_text', 100);
            $table->text('description');
            $table->text('keyword');
            $table->string('author', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website');
    }
};
