<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_inbox_messages')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('wa_inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 30)->index();
            $table->string('from_name', 150)->nullable();
            $table->enum('direction', ['in', 'out']);
            $table->text('body');
            $table->string('message_type', 30)->default('text');
            $table->string('meta_message_id', 100)->nullable();
            $table->string('status', 30)->default('sent');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['from_number', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_inbox_messages');
    }
};
