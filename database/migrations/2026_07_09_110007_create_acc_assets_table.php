<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable();
            $table->string('name', 150);
            $table->date('acquired_date');
            $table->decimal('acquisition_cost', 20, 2)->default(0);
            $table->decimal('salvage_value', 20, 2)->default(0);
            $table->unsignedInteger('useful_life_months')->default(60);
            // method: straight_line
            $table->string('method', 30)->default('straight_line');
            $table->decimal('accumulated_depreciation', 20, 2)->default(0);
            // linked accounts
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('accum_account_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->enum('status', ['active', 'disposed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_assets');
    }
};
