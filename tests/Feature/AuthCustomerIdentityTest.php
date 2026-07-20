<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthCustomerIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('email')->nullable();
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

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('nomor')->nullable();
        });
    }

    private function customer(string $email, string $nomor, string $name = 'Pelanggan'): User
    {
        return User::create([
            'nama' => $name,
            'email' => $email,
            'nomor' => $nomor,
            'password' => Hash::make('password123'),
            'balance' => 0,
            'level' => 'user',
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }

    public function test_customer_login_rejects_ambiguous_order_identity(): void
    {
        $this->customer('customer@example.test', '081234567890');
        DB::table('orders')->insert([
            ['idpel' => 'CUST-001', 'nama' => 'Pelanggan', 'email' => 'customer@example.test', 'nomor' => null],
            ['idpel' => 'CUST-002', 'nama' => 'Pelanggan', 'email' => null, 'nomor' => '081234567890'],
        ]);

        $this->post('/auth/auth', [
            'email' => 'customer@example.test',
            'password' => 'password123',
        ])->assertRedirect('/auth/login');

        $this->assertGuest();
    }

    public function test_customer_login_binds_exact_order_and_redirects_to_user_dashboard(): void
    {
        $this->customer('customer@example.test', '081234567890');
        DB::table('orders')->insert([
            'idpel' => 'CUST-001',
            'nama' => 'Pelanggan',
            'email' => 'customer@example.test',
            'nomor' => '081234567890',
        ]);

        $this->post('/auth/auth', [
            'email' => 'customer@example.test',
            'password' => 'password123',
        ])->assertRedirect('/user/dashboard');

        $this->assertAuthenticated();
        $this->assertSame('CUST-001', session('idpel'));
    }

    public function test_customer_login_rejects_duplicate_identifier_accounts(): void
    {
        $this->customer('customer@example.test', '081111111111', 'Satu');
        $this->customer('customer@example.test', '082222222222', 'Dua');

        $this->post('/auth/auth', [
            'email' => 'customer@example.test',
            'password' => 'password123',
        ])->assertRedirect('/auth/login');

        $this->assertGuest();
    }
}
