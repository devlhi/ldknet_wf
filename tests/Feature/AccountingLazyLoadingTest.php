<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingLazyLoadingTest extends TestCase
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
        Schema::create('acc_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('subtype')->nullable();
            $table->boolean('is_cash')->default(false);
            $table->decimal('opening_balance', 18, 2)->default(0);
        });
        Schema::create('acc_journals', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->date('date');
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total', 18, 2)->default(0);
            $table->boolean('is_posted')->default(true);
        });
        Schema::create('acc_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('memo')->nullable();
        });
        foreach (['acc_contacts', 'acc_products', 'acc_sales_invoices', 'acc_purchase_bills', 'acc_expenses', 'acc_assets'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
            });
        }
    }

    public function test_accounting_lists_require_explicit_show_data_before_primary_queries(): void
    {
        $user = $this->accountingUser();
        $pages = [
            '/admin/accounting/accounts' => 'acc_accounts',
            '/admin/accounting/journals' => 'acc_journals',
            '/admin/accounting/contacts' => 'acc_contacts',
            '/admin/accounting/products' => 'acc_products',
            '/admin/accounting/sales' => 'acc_sales_invoices',
            '/admin/accounting/purchases' => 'acc_purchase_bills',
            '/admin/accounting/expenses' => 'acc_expenses',
            '/admin/accounting/assets' => 'acc_assets',
        ];

        foreach ($pages as $uri => $primaryQuery) {
            $queries = $this->captureQueries(fn () => $this->actingAs($user)->get($uri)
                ->assertOk()
                ->assertSee('Tampilkan Data')
                ->assertSee('Klik Tampilkan Data untuk memuat data.'));

            $this->assertNotContainsQuery($primaryQuery, $queries, $uri);
        }
    }

    public function test_accounting_reports_skip_calculations_until_show_data_is_requested(): void
    {
        $user = $this->accountingUser();
        $pages = [
            '/admin/accounting/reports/ledger' => 'acc_journal_lines',
            '/admin/accounting/reports/trial-balance' => 'acc_journal_lines',
            '/admin/accounting/reports/profit-loss' => 'acc_journal_lines',
            '/admin/accounting/reports/balance-sheet' => 'acc_journal_lines',
            '/admin/accounting/reports/cash-flow' => 'acc_journal_lines',
        ];

        foreach ($pages as $uri => $primaryTable) {
            $queries = $this->captureQueries(fn () => $this->actingAs($user)->get($uri)
                ->assertOk()
                ->assertSee('Tampilkan Data')
                ->assertSee('Klik Tampilkan Data untuk memuat laporan.'));

            $this->assertNotContainsQuery($primaryTable, $queries, $uri);
        }
    }

    public function test_show_data_executes_list_and_report_queries(): void
    {
        $user = $this->accountingUser();

        $listQueries = $this->captureQueries(fn () => $this->actingAs($user)
            ->get('/admin/accounting/journals?show_data=1')
            ->assertOk());
        $this->assertContainsQuery('acc_journals', $listQueries);

        $reportQueries = $this->captureQueries(fn () => $this->actingAs($user)
            ->get('/admin/accounting/reports/trial-balance?show_data=1')
            ->assertOk());
        $this->assertContainsQuery('acc_journal_lines', $reportQueries);
    }

    private function accountingUser(): User
    {
        return User::create([
            'email' => 'accounting@example.test',
            'nama' => 'Accounting Test',
            'nomor' => '0800000000',
            'password' => bcrypt('password'),
            'balance' => 0,
            'level' => 'finance',
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }

    private function captureQueries(callable $request): array
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $request();

        return $queries;
    }

    private function assertNotContainsQuery(string $needle, array $queries, string $uri): void
    {
        $matching = array_values(array_filter($queries, fn (string $query): bool => str_contains($query, $needle)));
        $this->assertSame([], $matching, "Unexpected primary query for {$uri}:\n".implode("\n", $matching));
    }

    private function assertContainsQuery(string $needle, array $queries): void
    {
        $this->assertTrue(
            collect($queries)->contains(fn (string $query): bool => str_contains($query, $needle)),
            "Expected query containing [{$needle}].\n".implode("\n", $queries)
        );
    }
}
