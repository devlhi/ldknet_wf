<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gangguan_reports')) {
            return;
        }

        $reports = DB::table('gangguan_reports')->get();

        foreach ($reports as $report) {
            $pesan = trim((string) $report->pesan);

            if ($pesan === '') {
                DB::table('gangguan_reports')->where('id', $report->id)->delete();

                continue;
            }

            if (! preg_match('/\p{L}/u', $pesan)) {
                DB::table('gangguan_reports')->where('id', $report->id)->delete();

                continue;
            }

            $lower = mb_strtolower($pesan);
            if ($lower === 'wifi' || $lower === 'wi-fi') {
                DB::table('gangguan_reports')->where('id', $report->id)->delete();

                continue;
            }
        }
    }

    public function down(): void
    {
        // No rollback needed for cleaning up bad records.
    }
};
