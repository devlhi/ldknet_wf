<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable();
            $table->string('name', 150);
            // type: service, product (inventory)
            $table->enum('type', ['service', 'product'])->default('service');
            $table->string('unit', 30)->nullable();
            $table->decimal('sale_price', 20, 2)->default(0);
            $table->decimal('purchase_price', 20, 2)->default(0);
            $table->decimal('stock', 20, 2)->default(0);
            // linked accounts for auto journal
            $table->unsignedBigInteger('income_account_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_products');
    }
};
