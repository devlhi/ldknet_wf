<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_journals', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->date('date');
            // source: manual, invoice, bill, expense, payment, opening, adjustment
            $table->string('source', 30)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('total', 20, 2)->default(0);
            $table->boolean('is_posted')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index(['source', 'source_id']);
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_journals');
    }
};
