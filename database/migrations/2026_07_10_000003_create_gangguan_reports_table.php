<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gangguan_reports', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 30);
            $table->string('from_name', 191)->nullable();
            $table->string('idpel', 50)->nullable();
            $table->string('gateway', 20)->default('meta'); // meta | wablas | ...
            $table->string('kategori', 40)->default('lainnya');
            $table->text('pesan');
            $table->string('status', 20)->default('baru'); // baru | diproses | selesai
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
            $table->index('from_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gangguan_reports');
    }
};
