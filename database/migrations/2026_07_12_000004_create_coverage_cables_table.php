<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coverage_cables')) {
            return;
        }

        Schema::create('coverage_cables', function (Blueprint $table) {
            $table->id();
            // Cache jalur kabel hub->ODP hasil routing OSRM (agar tidak routing ulang tiap buka peta).
            $table->unsignedBigInteger('odp_id')->unique();
            $table->longText('path'); // JSON: [[lat,lng], ...] mengikuti jalan
            $table->string('src_hash', 160); // penanda koordinat hub+odp; beda hash = perlu routing ulang
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_cables');
    }
};
