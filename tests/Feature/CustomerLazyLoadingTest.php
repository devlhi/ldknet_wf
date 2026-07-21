<?php

namespace Tests\Feature;

use App\Libraries\RouterosAPI;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class CustomerLazyLoadingTest extends TestCase
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
        Schema::create('invoice', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('idpel');
            $table->date('date')->nullable();
            $table->string('status');
        });
        Schema::create('router', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('ip');
            $table->string('username');
            $table->string('password');
        });
        Schema::create('odp', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->integer('port');
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('paket');
            $table->integer('harga')->default(0);
            $table->integer('ppn')->default(0);
            $table->string('ppp_profile')->nullable();
            $table->string('status')->default('Tersedia');
            $table->string('mode')->default('pppoe');
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('paket');
            $table->string('id_router')->nullable();
            $table->string('mode')->nullable();
            $table->string('pppoe_user')->nullable();
            $table->date('date')->nullable();
            $table->date('expdate')->nullable();
            $table->string('status');
            $table->string('alamat')->nullable();
            $table->string('nama_odp')->nullable();
            $table->string('port_odp')->nullable();
            $table->string('nomor')->nullable();
        });

        $this->user = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'level' => 'admin',
            'status_account' => 'Active',
        ]);
    }

    public function test_customer_page_waits_for_explicit_activation(): void
    {
        $this->actingAs($this->user)->get('/admin/customers')
            ->assertOk()
            ->assertSee('Tampilkan Data')
            ->assertSee('customerTableContainer');

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $response = $this->actingAs($this->user)->getJson('/admin/customer/data');

        $response->assertExactJson([
            'draw' => 1,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains(strtolower($sql), 'orders')));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_customer_detail_eagerly_connects_router_without_show_data(): void
    {
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Customer Test',
            'paket' => '10 Mbps',
            'id_router' => 99,
            'mode' => 'pppoe',
            'pppoe_user' => 'customer-test',
            'date' => '2026-01-01',
            'expdate' => '2026-12-31',
            'status' => 'Active',
        ]);
        DB::table('router')->insert([
            'id' => 99,
            'nama' => 'Router Test',
            'ip' => '192.0.2.1',
            'username' => 'admin',
            'password' => legacy_encrypt('secret'),
        ]);

        $routerApi = Mockery::mock('overload:'.RouterosAPI::class);
        $routerApi->shouldReceive('connect')
            ->once()
            ->with('192.0.2.1', 'admin', 'secret')
            ->andReturnTrue();
        $routerApi->shouldReceive('comm')
            ->once()
            ->with('/interface/print')
            ->andReturn([]);
        $routerApi->shouldReceive('comm')
            ->once()
            ->with('/ppp/active/getall', [
                '.proplist' => '.id',
                '?name' => 'customer-test',
            ])
            ->andReturn([]);

        $this->actingAs($this->user)->get('/admin/customer/detail/CUST-001')
            ->assertOk()
            ->assertSee('Customer Test')
            ->assertDontSee('Data router pelanggan belum dimuat.')
            ->assertDontSee('Tampilkan Data');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_can_check_pppoe_connection_on_demand(): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'idpel' => 'CUST-CONNECTION',
            'nama' => 'Connection Test',
            'paket' => '10 Mbps',
            'id_router' => 99,
            'mode' => 'pppoe',
            'pppoe_user' => 'connection-user',
            'status' => 'Active',
        ]);
        DB::table('router')->insert([
            'id' => 99,
            'nama' => 'Router Test',
            'ip' => '192.0.2.1',
            'username' => 'admin',
            'password' => legacy_encrypt('secret'),
        ]);

        $routerApi = Mockery::mock('overload:'.RouterosAPI::class);
        $routerApi->shouldReceive('connect')->once()->with('192.0.2.1', 'admin', 'secret')->andReturnTrue();
        $routerApi->shouldReceive('comm')->once()->with('/ppp/active/getall', [
            '.proplist' => '.id',
            '?name' => 'connection-user',
        ])->andReturn([['.id' => '*1']]);
        $routerApi->shouldReceive('disconnect')->once();

        $this->actingAs($this->user)->getJson('/admin/customer/connection/'.$orderId)
            ->assertOk()
            ->assertJson(['online' => true, 'message' => 'Pelanggan online']);
    }

    public function test_connection_check_rejects_incomplete_router_data(): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'idpel' => 'CUST-OFFLINE',
            'nama' => 'Offline Test',
            'paket' => '10 Mbps',
            'status' => 'Active',
        ]);

        $this->actingAs($this->user)->getJson('/admin/customer/connection/'.$orderId)
            ->assertOk()
            ->assertJson(['online' => false, 'message' => 'Pelanggan offline']);
    }

    public function test_customer_filter_endpoint_is_guarded_without_activation(): void
    {
        $response = $this->actingAs($this->user)->getJson('/admin/customer/filter');
        $response->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'data' => [],
            ]);
    }

    public function test_customer_odp_assignment_rejects_occupied_port(): void
    {
        DB::table('odp')->insert(['nama' => 'ODP-01', 'port' => 8]);
        DB::table('orders')->insert([
            [
                'idpel' => 'CUST-001',
                'nama' => 'Customer One',
                'paket' => '10 Mbps',
                'status' => 'Active',
                'nama_odp' => null,
                'port_odp' => null,
            ],
            [
                'idpel' => 'CUST-002',
                'nama' => 'Customer Two',
                'paket' => '10 Mbps',
                'status' => 'Active',
                'nama_odp' => 'ODP-01',
                'port_odp' => '03',
            ],
        ]);

        $this->actingAs($this->user)->post('/admin/customer/update/odp', [
            'idpel' => 'CUST-001',
            'nama_odp' => 'ODP-01',
            'port_odp' => 3,
        ])->assertRedirect('/admin/customer/edit/CUST-001')
            ->assertSessionHas('errors_odp');

        $this->assertNull(DB::table('orders')->where('idpel', 'CUST-001')->value('port_odp'));
    }

    public function test_customer_odp_assignment_validates_capacity_and_updates_free_port(): void
    {
        DB::table('odp')->insert(['nama' => 'ODP-01', 'port' => 8]);
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Customer One',
            'paket' => '10 Mbps',
            'status' => 'Active',
        ]);

        $this->actingAs($this->user)->post('/admin/customer/update/odp', [
            'idpel' => 'CUST-001',
            'nama_odp' => 'ODP-01',
            'port_odp' => 9,
        ])->assertSessionHas('errors_odp');

        $this->actingAs($this->user)->post('/admin/customer/update/odp', [
            'idpel' => 'CUST-001',
            'nama_odp' => 'ODP-01',
            'port_odp' => 4,
        ])->assertSessionHas('success_odp');

        $this->assertSame('4', DB::table('orders')->where('idpel', 'CUST-001')->value('port_odp'));
    }

    public function test_customer_odp_assignment_uses_id_and_blocks_legacy_equivalent_port(): void
    {
        $odpId = DB::table('odp')->insertGetId(['nama' => 'Aping001(P.Hengin)', 'port' => 8]);
        DB::table('orders')->insert([
            [
                'idpel' => 'CUST-001',
                'nama' => 'Customer One',
                'paket' => '10 Mbps',
                'status' => 'Active',
                'nama_odp' => null,
                'port_odp' => null,
            ],
            [
                'idpel' => 'CUST-002',
                'nama' => 'Customer Two',
                'paket' => '10 Mbps',
                'status' => 'Active',
                'nama_odp' => ' APING001(P.HENG ',
                'port_odp' => '03',
            ],
        ]);

        $this->actingAs($this->user)->post('/admin/customer/update/odp', [
            'idpel' => 'CUST-001',
            'odp_id' => $odpId,
            'port_odp' => 3,
        ])->assertSessionHas('errors_odp');

        $this->actingAs($this->user)->post('/admin/customer/update/odp', [
            'idpel' => 'CUST-001',
            'odp_id' => $odpId,
            'port_odp' => 4,
        ])->assertSessionHas('success_odp');

        $this->assertSame('Aping001(P.Heng', DB::table('orders')->where('idpel', 'CUST-001')->value('nama_odp'));
        $usedPorts = $this->actingAs($this->user)->getJson('/get-used-ports');
        $usedPorts->assertJsonPath("data.{$odpId}.3.idpel", 'CUST-002')
            ->assertJsonPath("data.{$odpId}.4.idpel", 'CUST-001');
    }

    public function test_get_used_ports_resolves_exact_names_first_when_prefix_collides(): void
    {
        $odp1 = DB::table('odp')->insertGetId(['nama' => 'Aping001(P.Herpin)', 'port' => 8]);
        $odp2 = DB::table('odp')->insertGetId(['nama' => 'Aping001(P.Herpin_Dua)', 'port' => 8]);

        DB::table('orders')->insert([
            [
                'idpel' => 'CUST-A1',
                'nama' => 'Customer A1',
                'paket' => '10 Mbps',
                'status' => 'Active',
                'nama_odp' => 'Aping001(P.Herpin)',
                'port_odp' => '2',
            ],
            [
                'idpel' => 'CUST-A2',
                'nama' => 'Customer A2',
                'paket' => '10 Mbps',
                'status' => 'Active',
                'nama_odp' => 'Aping001(P.Herp',
                'port_odp' => '3',
            ],
        ]);

        $usedPorts = $this->actingAs($this->user)->getJson('/get-used-ports');

        $usedPorts->assertJsonPath("data.{$odp1}.2.idpel", 'CUST-A1');
        $this->assertFalse(isset($usedPorts->json()['data'][$odp1]['3']));
        $this->assertFalse(isset($usedPorts->json()['data'][$odp2]['3']));
    }

    public function test_customer_odp_assignment_rejects_ambiguous_legacy_prefix(): void
    {
        $firstId = DB::table('odp')->insertGetId(['nama' => 'Gontang001(Mayam Heri)', 'port' => 4]);
        DB::table('odp')->insert(['nama' => 'Gontang001(Mayam Anton)', 'port' => 4]);
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Customer One',
            'paket' => '10 Mbps',
            'status' => 'Active',
        ]);

        $this->actingAs($this->user)->post('/admin/customer/update/odp', [
            'idpel' => 'CUST-001',
            'odp_id' => $firstId,
            'port_odp' => 1,
        ])->assertSessionHas('errors_odp');

        $this->assertNull(DB::table('orders')->where('idpel', 'CUST-001')->value('nama_odp'));
    }

    public function test_customer_endpoint_loads_rows_after_activation(): void
    {
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Customer Test',
            'paket' => '10 Mbps',
            'status' => 'Active',
            'expdate' => '2027-01-01',
        ]);

        $response = $this->actingAs($this->user)->getJson('/admin/customer/data?show_data=1&draw=2&start=0&length=10');

        $response->assertOk()
            ->assertJsonPath('draw', 2)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonCount(1, 'data');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_customer_update_data_handles_profile_error_gracefully(): void
    {
        $routerId = DB::table('router')->insertGetId([
            'nama' => 'Main Router',
            'ip' => '127.0.0.1',
            'username' => 'admin',
            'password' => legacy_encrypt('pass'),
        ]);

        DB::table('services')->insert([
            'paket' => 'Paket 2-Device',
            'ppp_profile' => 'nonexistent_profile',
            'status' => 'Tersedia',
            'mode' => 'pppoe',
        ]);

        DB::table('orders')->insert([
            'idpel' => 'P-0422',
            'nama' => 'Bendu',
            'paket' => 'Paket 1-Device',
            'id_router' => (string) $routerId,
            'mode' => 'pppoe',
            'pppoe_user' => 'BENDU',
            'status' => 'Active',
        ]);

        $mockRos = Mockery::mock('overload:'.RouterosAPI::class);
        $mockRos->shouldReceive('connect')->andReturn(true);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/getall', Mockery::any())->andReturn([
            ['.id' => '*1'],
        ]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/set', Mockery::any())->andReturn([
            '!trap' => [['message' => 'input does not match any value of profile']],
        ]);

        $response = $this->actingAs($this->user)->post('/admin/customer/update', [
            'idpel' => 'P-0422',
            'name' => 'Bendu',
            'paket' => 'Paket 2-Device',
            'status' => 'Active',
        ]);

        $response->assertRedirect('/admin/customer/edit/P-0422')
            ->assertSessionHas('auth_errors', [
                "Profile Mikrotik 'nonexistent_profile' untuk paket ini tidak ditemukan pada router. Harap buat profile di Mikrotik atau sinkronkan ulang di menu Data Paket.",
            ]);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_customer_update_data_updates_package_and_status(): void
    {
        $routerId = DB::table('router')->insertGetId([
            'nama' => 'Main Router',
            'ip' => '127.0.0.1',
            'username' => 'admin',
            'password' => legacy_encrypt('pass'),
        ]);

        DB::table('services')->insert([
            'paket' => 'Paket 2-Device',
            'ppp_profile' => 'profile_2device',
            'status' => 'Tersedia',
            'mode' => 'pppoe',
        ]);

        DB::table('orders')->insert([
            'idpel' => 'P-0423',
            'nama' => 'Bendu2',
            'paket' => 'Paket 1-Device',
            'id_router' => (string) $routerId,
            'mode' => 'pppoe',
            'pppoe_user' => 'BENDU2',
            'status' => 'Isolir',
        ]);

        DB::table('users')->insert([
            'nama' => 'Bendu2',
            'email' => 'bendu2@example.test',
            'password' => 'secret',
            'level' => 'user',
            'status_account' => 'Isolir',
        ]);

        $mockRos = Mockery::mock('overload:'.RouterosAPI::class);
        $mockRos->shouldReceive('connect')->andReturn(true);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/getall', Mockery::any())->andReturn([
            ['.id' => '*1'],
        ]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/set', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/enable', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/active/getall', Mockery::any())->andReturn([]);

        $response = $this->actingAs($this->user)->post('/admin/customer/update', [
            'idpel' => 'P-0423',
            'name' => 'Bendu2',
            'paket' => 'Paket 2-Device',
            'status' => 'Active',
        ]);

        $response->assertRedirect('/admin/customer/edit/P-0423')
            ->assertSessionHas('success');

        $this->assertSame('Paket 2-Device', DB::table('orders')->where('idpel', 'P-0423')->value('paket'));
        $this->assertSame('Active', DB::table('orders')->where('idpel', 'P-0423')->value('status'));
        $this->assertSame('Active', DB::table('users')->where('nama', 'Bendu2')->value('status_account'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_customer_update_data_switches_pppoe_to_hotspot(): void
    {
        $routerId = DB::table('router')->insertGetId([
            'nama' => 'Main Router',
            'ip' => '127.0.0.1',
            'username' => 'admin',
            'password' => legacy_encrypt('pass'),
        ]);

        DB::table('services')->insert([
            'paket' => 'Paket Hotspot 5M',
            'ppp_profile' => 'profile_hs5m',
            'status' => 'Tersedia',
            'mode' => 'hotspot',
        ]);

        DB::table('orders')->insert([
            'idpel' => 'P-0424',
            'nama' => 'BenduHS',
            'paket' => 'Paket 1-Device',
            'id_router' => (string) $routerId,
            'mode' => 'pppoe',
            'pppoe_user' => 'BENDUHS',
            'status' => 'Active',
        ]);

        $mockRos = Mockery::mock('overload:'.RouterosAPI::class);
        $mockRos->shouldReceive('connect')->andReturn(true);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/getall', Mockery::any())->andReturn([
            ['.id' => '*1', 'password' => 'secret123'],
        ]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/remove', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/active/getall', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/user/print', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/user/add', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/user/enable', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/active/print', Mockery::any())->andReturn([]);

        $response = $this->actingAs($this->user)->post('/admin/customer/update', [
            'idpel' => 'P-0424',
            'name' => 'BenduHS',
            'paket' => 'Paket Hotspot 5M',
            'status' => 'Active',
        ]);

        $response->assertRedirect('/admin/customer/edit/P-0424')
            ->assertSessionHas('success');

        $this->assertSame('Paket Hotspot 5M', DB::table('orders')->where('idpel', 'P-0424')->value('paket'));
        $this->assertSame('hotspot', DB::table('orders')->where('idpel', 'P-0424')->value('mode'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_customer_update_data_switches_hotspot_to_pppoe(): void
    {
        $routerId = DB::table('router')->insertGetId([
            'nama' => 'Main Router',
            'ip' => '127.0.0.1',
            'username' => 'admin',
            'password' => legacy_encrypt('pass'),
        ]);

        DB::table('services')->insert([
            'paket' => 'Paket PPPoE 10M',
            'ppp_profile' => 'profile_pppoe10m',
            'status' => 'Tersedia',
            'mode' => 'pppoe',
        ]);

        DB::table('orders')->insert([
            'idpel' => 'P-0425',
            'nama' => 'BenduPPPoE',
            'paket' => 'Paket Hotspot 5M',
            'id_router' => (string) $routerId,
            'mode' => 'hotspot',
            'pppoe_user' => 'BENDUPPPOE',
            'status' => 'Active',
        ]);

        $mockRos = Mockery::mock('overload:'.RouterosAPI::class);
        $mockRos->shouldReceive('connect')->andReturn(true);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/user/print', Mockery::any())->andReturn([
            ['.id' => '*1', 'password' => 'secret456'],
        ]);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/user/remove', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ip/hotspot/active/print', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/getall', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/add', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/secret/enable', Mockery::any())->andReturn([]);
        $mockRos->shouldReceive('comm')->with('/ppp/active/getall', Mockery::any())->andReturn([]);

        $response = $this->actingAs($this->user)->post('/admin/customer/update', [
            'idpel' => 'P-0425',
            'name' => 'BenduPPPoE',
            'paket' => 'Paket PPPoE 10M',
            'status' => 'Active',
        ]);

        $response->assertRedirect('/admin/customer/edit/P-0425')
            ->assertSessionHas('success');

        $this->assertSame('Paket PPPoE 10M', DB::table('orders')->where('idpel', 'P-0425')->value('paket'));
        $this->assertSame('pppoe', DB::table('orders')->where('idpel', 'P-0425')->value('mode'));
    }
}
