<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coverage_odp_assignments')) {
            return;
        }

        Schema::create('coverage_odp_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odp_id')->unique();
            $table->unsignedBigInteger('coverage_odc_id');
            $table->timestamps();
            $table->index('coverage_odc_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_odp_assignments');
    }
};
