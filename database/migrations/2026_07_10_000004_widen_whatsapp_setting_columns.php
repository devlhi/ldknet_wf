<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perlebar api_url & api_key whatsapp_setting dari varchar(255) ke TEXT.
     *
     * Alasan: api_url menampung SELURUH setting Meta jadi satu blob (graph_url,
     * verify token, WABA id, nama template _v2, phone number id, app secret) yang
     * mudah menembus 255; api_key menampung Access Token Meta yang sering >255.
     *
     * Aman untuk CI4 (skema shared): kedua kolom TIDAK punya index (whatsapp_setting
     * hanya PRIMARY di id), widening varchar->TEXT tidak menghapus/mengubah data
     * lama dan CI4 tetap baca/tulis seperti biasa.
     */
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_setting')) {
            return;
        }
        if (Schema::hasColumn('whatsapp_setting', 'api_url')) {
            DB::statement('ALTER TABLE whatsapp_setting MODIFY api_url TEXT NOT NULL');
        }
        if (Schema::hasColumn('whatsapp_setting', 'api_key')) {
            DB::statement('ALTER TABLE whatsapp_setting MODIFY api_key TEXT NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_setting')) {
            return;
        }
        if (Schema::hasColumn('whatsapp_setting', 'api_url')) {
            DB::statement('ALTER TABLE whatsapp_setting MODIFY api_url VARCHAR(255) NOT NULL');
        }
        if (Schema::hasColumn('whatsapp_setting', 'api_key')) {
            DB::statement('ALTER TABLE whatsapp_setting MODIFY api_key VARCHAR(255) NOT NULL');
        }
    }
};
