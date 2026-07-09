<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change interface_name from varchar(100) to text to allow JSON array storage
        DB::statement('ALTER TABLE nms_sla_settings MODIFY COLUMN interface_name TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nms_sla_settings MODIFY COLUMN interface_name VARCHAR(100) NULL');
    }
};
