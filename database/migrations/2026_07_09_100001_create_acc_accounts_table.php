<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            // type: asset, liability, equity, revenue, expense
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            // sub classification e.g. current_asset, fixed_asset, cogs, other_income
            $table->string('subtype', 50)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_cash')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_accounts');
    }
};
