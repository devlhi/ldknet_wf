<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminRemainingTablesLazyLoadingTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email');
            $table->string('nomor')->nullable();
            $table->string('password');
            $table->string('level');
            $table->string('status_account')->default('Active');
        });
        Schema::create('website', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });
        Schema::create('area', function (Blueprint $table): void {
            $table->id();
            $table->string('kode');
            $table->string('nama');
        });
        Schema::create('odp', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_area')->nullable();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_area')->nullable();
        });
        Schema::create('katkas', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('jenis');
            $table->string('keterangan')->nullable();
        });
        Schema::create('nms_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('ip');
            $table->string('tipe');
            $table->string('status');
        });
        Schema::create('nms_sla_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('check_type')->default('ping');
            $table->json('interface_name')->nullable();
            $table->decimal('target_sla', 5, 2)->default(99.50);
            $table->boolean('enabled')->default(true);
        });

        $this->user = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'level' => 'admin',
            'status_account' => 'Active',
        ]);

        User::create([
            'nama' => 'Teknisi Lazy',
            'email' => 'teknisi@example.test',
            'password' => 'secret',
            'level' => 'technician',
            'status_account' => 'Active',
        ]);
        DB::table('area')->insert(['kode' => 'AR1', 'nama' => 'Area Lazy']);
        DB::table('katkas')->insert(['nama' => 'Kategori Lazy', 'jenis' => 'Pemasukan']);
        DB::table('nms_devices')->insert([
            'nama' => 'Device Lazy', 'ip' => '192.0.2.10', 'tipe' => 'router', 'status' => 'active',
        ]);
    }

    public function test_remaining_admin_tables_wait_for_explicit_activation(): void
    {
        $pages = [
            '/admin/karyawan' => ['Teknisi Lazy', 'users'],
            '/admin/coverage/area' => ['Area Lazy', 'area'],
            '/admin/finance/cash-flows/category' => ['Kategori Lazy', 'katkas'],
        ];

        DB::enableQueryLog();

        foreach ($pages as $uri => [$rowText, $primaryTable]) {
            DB::flushQueryLog();

            $this->actingAs($this->user)->get($uri)
                ->assertOk()
                ->assertSee('Tampilkan Data')
                ->assertDontSee($rowText);

            $queriedPrimaryTable = collect(DB::getQueryLog())->contains(
                fn (array $query): bool => str_contains(strtolower($query['query']), 'from "'.$primaryTable.'"')
            );
            $this->assertFalse($queriedPrimaryTable, "Initial GET queried primary table [$primaryTable].");

            $this->actingAs($this->user)->get($uri.'?show_data=1')
                ->assertOk()
                ->assertSee($rowText)
                ->assertDontSee('Tampilkan Data');
        }
    }
}
