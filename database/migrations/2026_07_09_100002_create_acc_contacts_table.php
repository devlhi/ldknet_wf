<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_contacts', function (Blueprint $table) {
            $table->id();
            // type: customer, vendor, both, employee
            $table->enum('type', ['customer', 'vendor', 'both', 'employee'])->default('customer');
            $table->string('code', 30)->nullable();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('tax_number', 40)->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_contacts');
    }
};
