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
        if (Schema::hasTable('payment_gateway')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('payment_gateway', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->string('code_merchant', 200);
            $table->string('api_url', 200);
            $table->string('api_key', 200);
            $table->string('private_key', 200);
            $table->string('callback', 10);
            $table->enum('status', ['enable', 'disable']);
            $table->string('url');
            $table->enum('sandbox', ['yes', 'no']);
            $table->integer('payment_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway');
    }
};
