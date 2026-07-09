<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cron_log')) {
            return;
        }

        Schema::create('cron_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('task', 50)->index();
            $table->string('status', 10)->default('running'); // running | success | failed
            $table->text('message')->nullable();
            $table->dateTime('started_at')->index();
            $table->dateTime('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_log');
    }
};
