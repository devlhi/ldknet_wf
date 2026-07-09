<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_purchase_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('acc_purchase_bills')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('description', 255);
            $table->decimal('qty', 20, 2)->default(1);
            $table->decimal('price', 20, 2)->default(0);
            $table->decimal('amount', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_purchase_bill_items');
    }
};
