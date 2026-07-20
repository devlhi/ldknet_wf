<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MenuLevelTablesLazyLoadingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel')->nullable();
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
        Schema::create('logs_voucher', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('time')->nullable();
            $table->string('kode');
            $table->string('service');
            $table->integer('harga');
        });
        Schema::create('orders_voucher', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('kode');
            $table->string('service');
            $table->integer('harga');
            $table->string('status');
        });
        Schema::create('hr_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->string('status');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('nama');
            $table->string('package');
            $table->string('status');
            $table->date('expdate');
        });
        Schema::create('invoice', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('idpel');
            $table->string('package');
            $table->integer('price');
            $table->string('status');
            $table->date('expdate');
        });

        DB::table('website')->insert(['title' => 'LandakNet Test', 'logo' => '']);

        // Register MySQL-compatible date functions so SQLite test DB can
        // execute raw YEAR()/MONTH() expressions used by VoucherController.
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('YEAR', fn ($date) => (int) date('Y', strtotime((string) $date)), 1);
        $pdo->sqliteCreateFunction('MONTH', fn ($date) => (int) date('n', strtotime((string) $date)), 1);
    }

    public function test_normal_gets_eagerly_query_row_datasets_without_activation_flags(): void
    {
        $admin = $this->user('admin', 'ADM-001');
        $technician = $this->user('technician', 'TECH-001');
        $customer = $this->user('user', 'CUS-001');

        DB::table('users')->insert([
            'idpel' => 'MEM-001', 'email' => 'member@example.test', 'nama' => 'Voucher Member',
            'nomor' => '0800000004', 'password' => bcrypt('password'), 'balance' => 0,
            'level' => 'member', 'verify_account' => 1, 'status_account' => 'Active',
        ]);
        DB::table('logs_voucher')->insert([
            'date' => '2026-07-16', 'time' => '10:00:00', 'kode' => 'LOG-001',
            'service' => 'Voucher 10K', 'harga' => 10000,
        ]);
        DB::table('orders_voucher')->insert([
            'date' => '2026-07-16', 'kode' => 'ORDER-001', 'service' => 'Voucher 20K',
            'harga' => 20000, 'status' => 'Paid',
        ]);
        DB::table('hr_attendances')->insert([
            'user_id' => $technician->id, 'tanggal' => '2026-07-16', 'status' => 'hadir',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('orders')->insert([
            'idpel' => 'CUS-001', 'nama' => 'Customer Test', 'package' => 'Paket Test',
            'status' => 'Active', 'expdate' => '2026-08-16',
        ]);
        DB::table('invoice')->insert([
            ['code' => 'INV-UNPAID', 'idpel' => 'CUS-001', 'package' => 'Paket Test', 'price' => 100000, 'status' => 'Unpaid', 'expdate' => '2026-08-16'],
            ['code' => 'INV-PAID', 'idpel' => 'CUS-001', 'package' => 'Paket Test', 'price' => 100000, 'status' => 'Paid', 'expdate' => '2026-07-16'],
        ]);

        $pages = [
            [$admin, '/server/voucher/users', 'users'],
            [$admin, '/server/voucher/report', 'logs_voucher'],
            [$admin, '/server/voucher/report/orders', 'orders_voucher'],
            [$technician, '/karyawan/absensi/history', 'hr_attendances'],
            [$customer, '/user/service', 'orders'],
            [$customer, '/user/invoice', 'invoice'],
            [$customer, '/user/invoice/history', 'invoice'],
        ];

        foreach ($pages as [$user, $uri, $table]) {
            $queries = $this->captureQueries(fn () => $this->actingAs($user)->get($uri)->assertOk());
            $this->assertContainsTableQuery($table, $queries, $uri);
        }
    }

    public function test_voucher_report_ajax_eagerly_queries_without_activation_flags(): void
    {
        $admin = $this->user('admin', 'ADM-001');

        $usageQueries = $this->captureQueries(fn () => $this->actingAs($admin)
            ->postJson('/server/voucher/report/filter', ['bulan' => 7, 'tahun' => 2026])
            ->assertOk());
        $this->assertContainsTableQuery('logs_voucher', $usageQueries, '/server/voucher/report/filter');

        $orderQueries = $this->captureQueries(fn () => $this->actingAs($admin)
            ->postJson('/server/voucher/report/orders/filter', ['bulan' => 7, 'tahun' => 2026])
            ->assertOk());
        $this->assertContainsTableQuery('orders_voucher', $orderQueries, '/server/voucher/report/orders/filter');
    }

    private function user(string $level, string $idpel): User
    {
        $id = DB::table('users')->insertGetId([
            'idpel' => $idpel,
            'email' => strtolower($level).'-'.$idpel.'@example.test',
            'nama' => ucfirst($level).' Test',
            'nomor' => '0800000000',
            'password' => bcrypt('password'),
            'balance' => 0,
            'level' => $level,
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);

        return User::findOrFail($id);
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

    private function assertNotContainsTableQuery(string $table, array $queries, string $uri): void
    {
        $matching = array_values(array_filter($queries, fn (string $query): bool => $this->queriesTable($query, $table)));
        $this->assertSame([], $matching, "Unexpected row query for {$uri}:\n".implode("\n", $matching));
    }

    private function assertContainsTableQuery(string $table, array $queries, string $uri): void
    {
        $this->assertTrue(
            collect($queries)->contains(fn (string $query): bool => $this->queriesTable($query, $table)),
            "Expected row query for {$uri} containing table [{$table}].\n".implode("\n", $queries)
        );
    }

    private function queriesTable(string $query, string $table): bool
    {
        return str_contains($query, 'from "'.$table.'"') || str_contains($query, 'from `'.$table.'`');
    }
}
