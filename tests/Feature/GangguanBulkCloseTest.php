<?php

namespace Tests\Feature;

use App\Models\GangguanReport;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
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
