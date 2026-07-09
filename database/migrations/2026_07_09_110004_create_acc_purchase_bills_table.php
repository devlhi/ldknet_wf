<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->unsignedBigInteger('contact_id');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount', 20, 2)->default(0);
            $table->decimal('tax', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->decimal('paid', 20, 2)->default(0);
            $table->enum('status', ['draft', 'unpaid', 'partial', 'paid', 'void'])->default('unpaid');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('contact_id');
            $table->index('status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_purchase_bills');
    }
};
