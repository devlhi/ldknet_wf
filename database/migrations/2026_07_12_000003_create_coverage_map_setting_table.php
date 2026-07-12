<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coverage_map_setting')) {
            return;
        }

        Schema::create('coverage_map_setting', function (Blueprint $table) {
            $table->id();
            // Titik pusat/OLT — sumber jalur kabel fiber ke tiap ODP.
            $table->string('hub_label', 100)->nullable();
            $table->decimal('hub_lat', 10, 7)->nullable();
            $table->decimal('hub_lng', 10, 7)->nullable();
            // Tampilan default peta (area view).
            $table->decimal('center_lat', 10, 7)->default(0.3);
            $table->decimal('center_lng', 10, 7)->default(109.5);
            $table->unsignedTinyInteger('zoom')->default(11);
            // Basemap default: streets | satelit | topografi | gelap
            $table->string('basemap', 20)->default('streets');
            $table->timestamps();
        });

        DB::table('coverage_map_setting')->insert([
            'center_lat' => 0.3,
            'center_lng' => 109.5,
            'zoom' => 11,
            'basemap' => 'streets',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_map_setting');
    }
};
