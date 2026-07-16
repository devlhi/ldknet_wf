<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TableAuditLazyLoadingTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('level');
            $table->string('status_account')->default('Active');
            $table->integer('verify_account')->default(1);
        });

        Schema::create('website', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });

        Schema::create('odp', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('kode')->nullable();
            $table->string('port')->nullable();
            $table->string('latitude');
            $table->string('longitude');
        });

        Schema::create('coverage_cables', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('odp_id');
            $table->text('path');
            $table->string('src_hash');
        });

        Schema::create('coverage_map_setting', function (Blueprint $table): void {
            $table->id();
            $table->string('hub_label')->nullable();
            $table->string('hub_lat')->nullable();
            $table->string('hub_lng')->nullable();
            $table->string('center_lat');
            $table->string('center_lng');
            $table->integer('zoom');
            $table->string('basemap');
        });

        Schema::create('logs_voucher', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->integer('harga');
        });

        Schema::create('template_message_voucher', function (Blueprint $table): void {
            $table->id();
            $table->text('notif_pembelian')->nullable();
            $table->text('notif_pembayaran')->nullable();
        });

        Schema::create('template_message', function (Blueprint $table): void {
            $table->id();
            $table->text('notif_tagihan')->nullable();
        });

        Schema::create('acc_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->boolean('is_cash')->default(false);
            $table->decimal('opening_balance', 18, 2)->default(0);
        });

        Schema::create('acc_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
        });

        Schema::create('acc_journals', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->date('date');
            $table->decimal('total', 18, 2)->default(0);
            $table->boolean('is_posted')->default(true);
        });

        Schema::create('acc_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('nama');
            $table->string('paket')->nullable();
            $table->string('status')->default('Active');
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('paket');
            $table->string('status');
        });

        Schema::create('nms_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('tipe');
            $table->string('ip');
            $table->integer('port')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('status');
        });

        Schema::create('nms_metrics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('metric_type');
            $table->string('port_name')->nullable();
            $table->string('value')->nullable();
            $table->timestamp('recorded_at')->nullable();
        });

        Schema::create('nms_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('device_a_id');
            $table->unsignedBigInteger('device_b_id');
            $table->string('port_a')->nullable();
            $table->string('port_b')->nullable();
            $table->string('status')->nullable();
        });

        $this->user = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'level' => 'admin',
            'status_account' => 'Active',
        ]);

        DB::table('coverage_map_setting')->insert([
            'center_lat' => '-0.68',
            'center_lng' => '109.12',
            'zoom' => 12,
            'basemap' => 'streets',
        ]);
    }

    public function test_audit_pages_do_not_query_row_datasets_on_initial_get(): void
    {
        $pages = [
            '/admin/coverage/peta' => 'odp',
            '/server/voucher/dashboard' => 'logs_voucher',
            '/admin/accounting' => 'acc_journals',
            '/admin/template/message' => 'template_message',
            '/server/voucher/template/message' => 'template_message_voucher',
            '/admin/finance/invoice/generate' => 'orders',
        ];

        DB::enableQueryLog();

        foreach ($pages as $uri => $primaryTable) {
            DB::flushQueryLog();

            $response = $this->actingAs($this->user)->get($uri);
            $response->assertOk()->assertSee('Tampilkan Data');

            $queriedPrimary = collect(DB::getQueryLog())->contains(
                fn (array $query): bool => str_contains(strtolower($query['query']), 'from "'.$primaryTable.'"')
            );
            $this->assertFalse($queriedPrimary, "Initial GET for {$uri} queried table [{$primaryTable}] eagerly.");
        }
    }

    public function test_audit_pages_load_data_upon_explicit_activation(): void
    {
        DB::table('odp')->insert(['nama' => 'ODP-LAZY', 'latitude' => '-0.68', 'longitude' => '109.12']);
        DB::table('logs_voucher')->insert(['date' => now()->toDateString(), 'harga' => 10000]);
        DB::table('template_message_voucher')->insert(['notif_pembelian' => 'Voucher baru']);
        DB::table('template_message')->insert(['notif_tagihan' => 'Tagihan baru']);
        DB::table('acc_journals')->insert(['number' => 'JV-001', 'date' => '2026-07-16', 'total' => 150000]);
        DB::table('orders')->insert(['idpel' => 'CUST-LAZY', 'nama' => 'Lazy Cust']);

        $pages = [
            '/admin/coverage/peta' => 'ODP-LAZY',
            '/server/voucher/dashboard' => '10,000',
            '/admin/accounting' => 'JV-001',
            '/admin/template/message' => 'Tagihan baru',
            '/server/voucher/template/message' => 'Voucher baru',
            '/admin/finance/invoice/generate' => 'CUST-LAZY',
        ];

        foreach ($pages as $uri => $needleText) {
            $response = $this->actingAs($this->user)->get($uri.'?show_data=1');
            $response->assertOk()
                ->assertSee($needleText)
                ->assertDontSee('Tampilkan Data');
        }
    }

    public function test_nms_map_data_requires_activation_for_admin_and_signature_for_public(): void
    {
        DB::table('nms_devices')->insert([
            'nama' => 'NMS-LAZY',
            'tipe' => 'router',
            'ip' => '192.0.2.10',
            'port' => 8728,
            'latitude' => '-0.68',
            'longitude' => '109.12',
            'status' => 'active',
        ]);

        $this->actingAs($this->user)->getJson('/admin/nms/map-data')
            ->assertExactJson(['data' => [], 'links' => []]);

        auth()->logout();

        $this->getJson('/nms/monitor/data/map')->assertForbidden();

        $this->getJson(URL::signedRoute('nms.public.map-data'))
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'NMS-LAZY');
    }
}
