<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('acc_assets')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 20, 2)->default(0);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();

            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_asset_depreciations');
    }
};
