<?php

namespace Tests\Feature;

use App\Models\GangguanReport;
use App\Models\GangguanSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GangguanBulkCloseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 40);
            $table->string('nama', 50);
            $table->string('nomor', 15);
            $table->text('password');
            $table->integer('balance');
            $table->string('level');
            $table->integer('verify_account');
            $table->string('status_account');
        });

        Schema::create('gangguan_reports', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 30);
            $table->string('from_name', 191)->nullable();
            $table->string('idpel', 50)->nullable();
            $table->string('nama_odp', 191)->nullable();
            $table->string('kode_area', 50)->nullable();
            $table->string('gateway', 20)->default('meta');
            $table->string('kategori', 40)->default('lainnya');
            $table->text('pesan');
            $table->string('status', 20)->default('baru');
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->boolean('auto_reply_sent')->default(false);
            $table->timestamps();
        });
        Schema::create('odp', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('port')->default(8);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('idpel')->nullable();
            $table->string('nama')->nullable();
            $table->string('nomor')->nullable();
            $table->string('status')->nullable();
            $table->string('nama_odp')->nullable();
        });
        Schema::create('gangguan_setting', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_reply_enabled')->default(true);
            $table->text('auto_reply_text')->nullable();
            $table->integer('sla_response_hours')->default(3);
            $table->integer('massal_threshold')->default(3);
            $table->integer('massal_window_hours')->default(6);
            $table->text('massal_broadcast_text')->nullable();
            $table->timestamps();
        });
        Schema::create('whatsapp_setting', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('mode')->nullable();
            $table->string('api_url')->nullable();
            $table->string('api_key')->nullable();
            $table->string('sender')->nullable();
        });

        Carbon::setTestNow(Carbon::parse('2026-07-13 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_bulk_closes_only_open_selected_reports(): void
    {
        $admin = $this->createUser('admin');
        $existingResponse = now()->subMinutes(30);
        $existingResolution = now()->subHours(2);

        $baru = $this->createReport([
            'status' => 'baru',
            'catatan' => 'Catatan laporan baru',
        ]);
        $diproses = $this->createReport([
            'status' => 'diproses',
            'responded_at' => $existingResponse,
            'catatan' => 'Sedang diperiksa',
        ]);
        $selesai = $this->createReport([
            'status' => 'selesai',
            'responded_at' => now()->subHours(3),
            'resolved_at' => $existingResolution,
            'handled_by' => 999,
            'catatan' => 'Sudah selesai sebelumnya',
        ]);

        $response = $this->actingAs($admin)->post('admin/gangguan/bulk-close', [
            'ids' => [$baru->id, $diproses->id, $selesai->id],
            'periode' => 'harian',
            'tanggal' => '2026-07-13',
            'f_status' => 'baru',
            'f_kategori' => 'wifi',
        ]);

        $response->assertRedirect(url('admin/gangguan').'?'.http_build_query([
            'periode' => 'harian',
            'tanggal' => '2026-07-13',
            'status' => 'baru',
            'kategori' => 'wifi',
        ]));
        $response->assertSessionHas('success', ['2 laporan gangguan ditutup']);

        $baru->refresh();
        $diproses->refresh();
        $selesai->refresh();

        $this->assertSame('selesai', $baru->status);
        $this->assertTrue($baru->responded_at->equalTo(now()));
        $this->assertTrue($baru->resolved_at->equalTo(now()));
        $this->assertSame($admin->id, $baru->handled_by);
        $this->assertSame('Catatan laporan baru', $baru->catatan);

        $this->assertSame('selesai', $diproses->status);
        $this->assertTrue($diproses->responded_at->equalTo($existingResponse));
        $this->assertTrue($diproses->resolved_at->equalTo(now()));
        $this->assertSame($admin->id, $diproses->handled_by);
        $this->assertSame('Sedang diperiksa', $diproses->catatan);

        $this->assertSame('selesai', $selesai->status);
        $this->assertTrue($selesai->resolved_at->equalTo($existingResolution));
        $this->assertSame(999, $selesai->handled_by);
        $this->assertSame('Sudah selesai sebelumnya', $selesai->catatan);
    }

    public function test_bulk_close_requires_at_least_one_report_id(): void
    {
        $admin = $this->createUser('admin');
        $report = $this->createReport(['status' => 'baru']);

        $response = $this->actingAs($admin)->post('admin/gangguan/bulk-close');

        $response->assertRedirect(url('admin/gangguan'));
        $response->assertSessionHas('auth_errors');
        $this->assertSame('baru', $report->fresh()->status);
    }

    public function test_admin_closes_all_open_reports_in_active_period_and_filter(): void
    {
        $admin = $this->createUser('admin');
        $baruWifi = $this->createReport(['status' => 'baru', 'kategori' => 'wifi']);
        $diprosesWifi = $this->createReport(['status' => 'diproses', 'kategori' => 'wifi']);
        $kategoriLain = $this->createReport(['status' => 'baru', 'kategori' => 'internet_mati']);
        $tanggalLain = $this->createReport([
            'status' => 'baru',
            'kategori' => 'wifi',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $sudahSelesai = $this->createReport([
            'status' => 'selesai',
            'kategori' => 'wifi',
            'resolved_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->post('admin/gangguan/bulk-close', [
            'close_all' => '1',
            'periode' => 'harian',
            'tanggal' => '2026-07-13',
            'f_kategori' => 'wifi',
        ]);

        $response->assertSessionHas('success', ['2 laporan gangguan ditutup']);
        $this->assertSame('selesai', $baruWifi->fresh()->status);
        $this->assertSame('selesai', $diprosesWifi->fresh()->status);
        $this->assertSame('baru', $kategoriLain->fresh()->status);
        $this->assertSame('baru', $tanggalLain->fresh()->status);
        $this->assertSame('selesai', $sudahSelesai->fresh()->status);
        $this->assertSame($admin->id, $baruWifi->fresh()->handled_by);
        $this->assertSame($admin->id, $diprosesWifi->fresh()->handled_by);
    }

    public function test_non_admin_cannot_bulk_close_reports(): void
    {
        $user = $this->createUser('user');
        $report = $this->createReport(['status' => 'baru']);

        $response = $this->actingAs($user)->post('admin/gangguan/bulk-close', [
            'ids' => [$report->id],
        ]);

        $response->assertRedirect(url('user'));
        $this->assertSame('baru', $report->fresh()->status);
        $this->assertNull($report->fresh()->resolved_at);
    }

    public function test_broadcast_rejects_unknown_and_ambiguous_odp_ids(): void
    {
        $admin = $this->createUser('admin');
        GangguanSetting::create(array_merge(GangguanSetting::defaults(), [
            'massal_broadcast_text' => 'Gangguan {odp}{nama}',
        ]));
        $firstId = DB::table('odp')->insertGetId(['nama' => 'Gontang001(Mayam Heri)']);
        DB::table('odp')->insert(['nama' => 'Gontang001(Mayam Anton)']);

        $this->actingAs($admin)->post('admin/gangguan/broadcast-odp', [
            'odp_id' => 999,
        ])->assertSessionHas('auth_errors');
        $this->actingAs($admin)->post('admin/gangguan/broadcast-odp', [
            'odp_id' => $firstId,
        ])->assertSessionHas('auth_errors');
    }

    public function test_broadcast_accepts_unique_legacy_assignment_and_ignores_other_odps(): void
    {
        $admin = $this->createUser('admin');
        GangguanSetting::create(array_merge(GangguanSetting::defaults(), [
            'massal_broadcast_text' => 'Gangguan {odp}{nama}',
        ]));
        $odpId = DB::table('odp')->insertGetId(['nama' => 'Aping001(P.Hengin)']);
        DB::table('odp')->insert(['nama' => 'ODP Lain']);
        DB::table('orders')->insert([
            ['nama' => 'Target', 'nomor' => '628111111112', 'status' => 'Active', 'nama_odp' => ' APING001(P.HENG '],
            ['nama' => 'Inactive', 'nomor' => '628111111113', 'status' => 'Inactive', 'nama_odp' => 'aping001(p.heng'],
            ['nama' => 'Other', 'nomor' => '628111111114', 'status' => 'Active', 'nama_odp' => 'ODP Lain'],
        ]);

        $response = $this->actingAs($admin)->post('admin/gangguan/broadcast-odp', [
            'odp_id' => $odpId,
        ]);

        $response->assertSessionHas('success', [
            'Broadcast gangguan massal ODP Aping001(P.Hengin) terkirim ke 1 pelanggan.',
        ]);
    }

    public function test_gangguan_report_classification_and_auto_reply(): void
    {
        $this->assertSame('internet_mati', GangguanReport::classify('Internet saya los merah'));
        $this->assertSame('wifi', GangguanReport::classify('Wifi tidak bisa konek'));
        $this->assertSame('lainnya', GangguanReport::classify('Ada gangguan di jaringan'));

        $this->assertNull(GangguanReport::classify('wifi'));
        $this->assertNull(GangguanReport::classify('wi-fi'));

        $this->assertNull(GangguanReport::classify('←🏀'));
        $this->assertNull(GangguanReport::classify('⏰🧺🧺'));
        $this->assertNull(GangguanReport::classify('👍😊'));
    }

    public function test_gibberish_reports_are_deleted_by_migration_logic(): void
    {
        $goodReport = GangguanReport::create([
            'from_number' => '628123456789',
            'from_name' => 'John Doe',
            'pesan' => 'Internet saya putus',
            'status' => 'baru',
        ]);

        $gibberishReport1 = GangguanReport::create([
            'from_number' => '628123456789',
            'from_name' => 'Luwis',
            'pesan' => '←🏀',
            'status' => 'baru',
        ]);

        $gibberishReport2 = GangguanReport::create([
            'from_number' => '628123456789',
            'from_name' => 'Luwis',
            'pesan' => 'wifi',
            'status' => 'baru',
        ]);

        // Run the same logic as the migration
        $reports = GangguanReport::all();
        foreach ($reports as $report) {
            $pesan = trim((string) $report->pesan);
            if ($pesan === '' || ! preg_match('/\p{L}/u', $pesan) || mb_strtolower($pesan) === 'wifi' || mb_strtolower($pesan) === 'wi-fi') {
                $report->delete();
            }
        }

        $this->assertTrue(GangguanReport::where('id', $goodReport->id)->exists());
        $this->assertFalse(GangguanReport::where('id', $gibberishReport1->id)->exists());
        $this->assertFalse(GangguanReport::where('id', $gibberishReport2->id)->exists());
    }

    private function createUser(string $level): User
    {
        return User::create([
            'email' => $level.'@example.test',
            'nama' => ucfirst($level),
            'nomor' => '628111111111',
            'password' => 'secret',
            'balance' => 0,
            'level' => $level,
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }

    private function createReport(array $overrides = []): GangguanReport
    {
        return GangguanReport::create(array_merge([
            'from_number' => '628123456789',
            'from_name' => 'Pelanggan Test',
            'gateway' => 'meta',
            'kategori' => 'internet_mati',
            'pesan' => 'Internet pelanggan sedang mati.',
            'status' => 'baru',
        ], $overrides));
    }
}
