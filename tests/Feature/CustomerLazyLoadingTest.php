<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('nama');
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

    public function test_customer_detail_requires_show_data_to_connect_router(): void
    {
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Customer Test',
            'paket' => '10 Mbps',
            'id_router' => 99,
            'mode' => 'pppoe',
            'date' => '2026-01-01',
            'expdate' => '2026-12-31',
            'status' => 'Active',
        ]);
        DB::table('router')->insert([
            'id' => 99,
            'nama' => 'Router Test',
        ]);

        // Detail page without show_data should load with placeholder notice
        $response = $this->actingAs($this->user)->get('/admin/customer/detail/CUST-001');
        $response->assertOk()
            ->assertSee('Data router pelanggan belum dimuat.')
            ->assertSee('Tampilkan Data');
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
