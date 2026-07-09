<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->date('date');
            $table->unsignedBigInteger('contact_id')->nullable();
            // expense account (debit) and paid-from cash/bank account (credit)
            $table->unsignedBigInteger('expense_account_id');
            $table->unsignedBigInteger('payment_account_id');
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_expenses');
    }
};
