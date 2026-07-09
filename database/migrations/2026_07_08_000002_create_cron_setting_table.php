<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tabel BARU khusus Laravel (tidak menyentuh tabel CI4 yang di-share produksi).
// Menyimpan jadwal cron per task agar user bisa atur waktunya dari UI.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cron_setting')) {
            return;
        }

        Schema::create('cron_setting', function (Blueprint $table) {
            $table->increments('id');
            $table->string('task', 50)->unique();
            $table->string('label', 100);
            $table->string('time', 5)->default('00:00'); // format HH:MM
            $table->boolean('enabled')->default(true);
        });

        DB::table('cron_setting')->insert([
            ['task' => 'updatestatus', 'label' => 'Update Status (Active → Isolir)', 'time' => '00:05', 'enabled' => 1],
            ['task' => 'cetakinv', 'label' => 'Generate Invoice', 'time' => '01:00', 'enabled' => 1],
            ['task' => 'isolir', 'label' => 'Isolir Pelanggan (putus koneksi Mikrotik)', 'time' => '01:30', 'enabled' => 1],
            ['task' => 'reminder', 'label' => 'Reminder Tagihan (WhatsApp)', 'time' => '08:00', 'enabled' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_setting');
    }
};
