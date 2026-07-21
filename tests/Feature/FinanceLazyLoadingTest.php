<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceLazyLoadingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->string('nama')->nullable();
            $table->string('nomor')->nullable();
            $table->string('password');
            $table->integer('balance')->default(0);
            $table->string('level');
            $table->integer('verify_account')->default(1);
            $table->string('status_account')->default('Active');
        });
        Schema::create('website', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });
        Schema::create('invoice', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('idpel')->nullable();
            $table->string('package')->nullable();
            $table->integer('received')->default(0);
            $table->date('date')->nullable();
            $table->date('expdate')->nullable();
            $table->dateTime('last_update')->nullable();
            $table->string('status')->nullable();
            $table->string('account')->nullable();
            $table->string('update_by')->nullable();
        });
        Schema::create('report', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('category')->nullable();
            $table->string('jenis_kategori')->nullable();
            $table->integer('balance')->default(0);
            $table->date('date')->nullable();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('nama');
            $table->string('paket');
            $table->date('date')->nullable();
            $table->string('status');
        });
    }

    public function test_finance_cashflow_and_report_ajax_endpoints_eagerly_return_unflagged_payloads(): void
    {
        $user = $this->financeUser();

        DB::table('invoice')->insert([
            'code' => 'INV-EAGER',
            'idpel' => 'CUST-001',
            'package' => '10 Mbps',
            'received' => 150000,
            'date' => '2026-01-10',
            'expdate' => '2026-01-20',
            'last_update' => '2026-01-11 08:00:00',
            'status' => 'Paid',
            'account' => 'user',
            'update_by' => 'Finance Test',
        ]);
        DB::table('report')->insert([
            'code' => 'CASH-EAGER',
            'category' => 'Internet',
            'jenis_kategori' => 'Pemasukan',
            'balance' => 150000,
            'date' => '2026-01-10',
        ]);

        // Invoice data keeps show_data lazy loading while cashflow/report are eager.
        $this->actingAs($user)->getJson('/finance/invoice/filter/ambil-data/1/2026')->assertExactJson([
            'getAllCredit' => 0,
            'getInvoicePaid' => 0,
            'getInvoiceUnpaid' => 0,
        ]);

        $this->actingAs($user)->postJson('/finance/cash-flows/filter/getdata', [
            'bulan' => 1,
            'tahun' => 2026,
        ])->assertJsonCount(1)
            ->assertJsonPath('0.code', 'CASH-EAGER');
        $this->actingAs($user)->getJson('/finance/cash-flows/filter/ambil-data/1/2026')->assertExactJson([
            'getDataPemasukan' => 150000,
            'getDataPengeluaran' => 0,
        ]);
        $this->actingAs($user)->postJson('/finance/report/filter/getdata', [
            'bulan' => 1,
            'tahun' => 2026,
        ])->assertJsonCount(1)
            ->assertJsonPath('0.code', 'INV-EAGER');
        $this->actingAs($user)->getJson('/finance/report/filter/ambil-data-statistik/1/2026')->assertExactJson([
            'getAllCredit' => 0,
            'getInvoicePaid' => 0,
            'getInvoiceUnpaid' => 0,
        ]);
    }

    public function test_invoice_data_requires_activation_and_defaults_to_year_2026(): void
    {
        DB::table('invoice')->insert([
            'code' => 'INV-LAZY',
            'date' => '2025-01-01',
            'status' => 'Unpaid',
            'account' => 'user',
        ]);

        $response = $this->actingAs($this->financeUser())->get('/finance/invoice');

        $response->assertOk()
            ->assertSee('Tampilkan Data')
            ->assertSee('id="datatable-invoices"', false)
            ->assertSee('value="2026" selected', false)
            ->assertDontSee('INV-LAZY');
    }

    public function test_finance_report_filters_eagerly_return_unflagged_payloads(): void
    {
        $user = $this->financeUser();

        DB::table('invoice')->insert([
            'code' => 'INV-REPORT',
            'idpel' => 'CUST-001',
            'package' => '10 Mbps',
            'date' => '2026-01-10',
            'status' => 'Paid',
            'account' => 'user',
        ]);
        DB::table('report')->insert([
            'code' => 'CASH-REPORT',
            'category' => 'Internet',
            'jenis_kategori' => 'Pemasukan',
            'balance' => 150000,
            'date' => '2026-01-10',
        ]);
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Customer Report',
            'paket' => '10 Mbps',
            'date' => '2026-01-10',
            'status' => 'Active',
        ]);

        $requests = [
            ['/admin/finance/report/filter', [
                'paket' => 'Pilih Paket', 'status' => 'Tampilkan Semua',
                'penerima' => 'Tampilkan Semua', 'bulan' => 1, 'tahun' => 2026,
            ], 'INV-REPORT'],
            ['/admin/finance/report/cash-flows/filter', [
                'bulan' => 1, 'tahun' => 2026,
                'kategori' => 'Tampilkan Semua', 'jenis' => 'Tampilkan Semua',
            ], 'CASH-REPORT'],
            ['/admin/finance/report/customers/filter', [
                'paket' => 'Tampilkan Semua', 'status' => 'Tampilkan Semua',
            ], 'CUST-001'],
            ['/admin/finance/report/new/customers/filter', [
                'bulan' => 1, 'tahun' => 2026,
            ], 'CUST-001'],
        ];

        foreach ($requests as [$uri, $payload, $expected]) {
            $key = str_contains($expected, 'REPORT') ? 'code' : 'idpel';

            $this->actingAs($user)->postJson($uri, $payload)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.'.$key, $expected);
        }
    }

    public function test_generate_invoice_view_renders_form_properly(): void
    {
        DB::table('orders')->insert([
            'idpel' => 'CUST-GEN',
            'nama' => 'Bendu',
            'paket' => 'Paket 2-Device',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->financeUser())->get('/admin/finance/invoice/generate');

        $response->assertOk()
            ->assertSee('Generate Invoice')
            ->assertSee('Bulan Tagihan')
            ->assertSee('Tahun Tagihan')
            ->assertSee('CUST-GEN - Bendu');
    }

    private function financeUser(): User
    {
        return User::create([
            'email' => 'finance-lazy@example.test',
            'nama' => 'Finance Lazy Test',
            'nomor' => '0800000000',
            'password' => bcrypt('password'),
            'balance' => 0,
            'level' => 'finance',
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }
}
