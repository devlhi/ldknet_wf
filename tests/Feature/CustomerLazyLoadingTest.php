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
}
