<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function test_finance_ajax_endpoints_return_empty_payload_without_activation(): void
    {
        $user = $this->financeUser();

        $this->actingAs($user)->postJson('/finance/invoice/filter/getdata')->assertExactJson([]);
        $this->actingAs($user)->getJson('/finance/invoice/filter/ambil-data/1/2026')->assertExactJson([
            'getAllCredit' => 0,
            'getInvoicePaid' => 0,
            'getInvoiceUnpaid' => 0,
        ]);
        $this->actingAs($user)->postJson('/finance/cash-flows/filter/getdata')->assertExactJson([]);
        $this->actingAs($user)->getJson('/finance/cash-flows/filter/ambil-data/1/2026')->assertExactJson([
            'getDataPemasukan' => 0,
            'getDataPengeluaran' => 0,
        ]);
        $this->actingAs($user)->postJson('/finance/report/filter/getdata')->assertExactJson([]);
        $this->actingAs($user)->getJson('/finance/report/filter/ambil-data-statistik/1/2026')->assertExactJson([
            'getAllCredit' => 0,
            'getInvoicePaid' => 0,
            'getInvoiceUnpaid' => 0,
        ]);
    }

    public function test_finance_report_filters_return_empty_payload_without_activation(): void
    {
        $user = $this->financeUser();

        foreach ([
            '/admin/finance/report/filter',
            '/admin/finance/report/cash-flows/filter',
            '/admin/finance/report/customers/filter',
            '/admin/finance/report/new/customers/filter',
        ] as $uri) {
            $this->actingAs($user)->postJson($uri)->assertExactJson(['data' => []]);
        }
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
