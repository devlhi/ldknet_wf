<?php

namespace Tests\Feature;

use App\Http\Controllers\CallbackController;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceInvoiceGatewayHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-21 10:00:00', 'Asia/Jakarta'));

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
            $table->string('code');
            $table->string('idpel');
            $table->string('nama');
            $table->string('category')->default('');
            $table->string('service')->default('');
            $table->string('method')->default('');
            $table->text('penerima')->default('');
            $table->string('metode_pembayaran')->default('');
            $table->string('package');
            $table->integer('price');
            $table->integer('random_price')->default(0);
            $table->integer('received')->default(0);
            $table->string('status');
            $table->string('reference')->default('');
            $table->date('date');
            $table->date('expdate');
            $table->string('exppay')->default('');
            $table->date('last_update');
            $table->string('payment_url')->nullable();
            $table->string('qr_url')->nullable();
            $table->text('update_by')->default('');
            $table->string('bukti_pembayaran')->default('');
            $table->string('data_invoice')->default('');
            $table->string('account');
            $table->string('code_coupon')->default('');
            $table->string('otp')->default('');
            $table->string('provider')->default('');
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('idpel');
            $table->string('email')->default('');
            $table->string('nama');
            $table->string('paket');
            $table->string('alamat')->default('');
            $table->string('nomor')->default('');
            $table->string('status');
            $table->date('date');
            $table->date('expdate');
            $table->text('pppoe_user')->default('');
            $table->string('id_router')->default('');
            $table->string('mode')->default('pppoe');
            $table->string('nama_odp')->default('');
            $table->string('port_odp')->default('');
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('paket');
            $table->string('ppp_profile')->default('default');
            $table->float('harga')->default(150000);
            $table->float('ppn')->default(0);
            $table->string('status')->default('Tersedia');
            $table->string('mode')->default('pppoe');
        });
        Schema::create('report', function (Blueprint $table): void {
            $table->id();
            $table->text('category');
            $table->string('jenis_kategori');
            $table->integer('balance');
            $table->string('asal');
            $table->dateTime('date');
            $table->string('image')->default('');
            $table->string('account')->default('');
        });
        Schema::create('template_message', function (Blueprint $table): void {
            $table->id();
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_edit_page_only_shows_gateway_bypass_for_active_transaction(): void
    {
        $active = $this->createInvoice('INV-ACTIVE', 'CUST-ACTIVE', '22-07-2026 10:00:00');
        $expired = $this->createInvoice('INV-EXPIRED', 'CUST-EXPIRED', '20-07-2026 10:00:00');
        $user = $this->financeUser();

        $this->actingAs($user)
            ->get('/admin/finance/invoice/edit/'.$active->code)
            ->assertOk()
            ->assertSee('name="bypass_gateway"', false)
            ->assertSee('Abaikan transaksi online aktif (Bypass)');

        $this->actingAs($user)
            ->get('/admin/finance/invoice/edit/'.$expired->code)
            ->assertOk()
            ->assertDontSee('name="bypass_gateway"', false);
    }

    public function test_active_gateway_transaction_remains_blocked_without_explicit_bypass(): void
    {
        $invoice = $this->seedPayableInvoice('INV-BLOCKED', 'CUST-BLOCKED', '22-07-2026 10:00:00');

        $response = $this->actingAs($this->financeUser())
            ->post('/admin/finance/invoice/update', $this->paymentPayload($invoice));

        $response->assertRedirect('admin/finance/invoice')
            ->assertSessionHas('auth_errors', fn (array $errors): bool => str_contains($errors[0], 'transaksi pembayaran online aktif'));

        $invoice->refresh();
        $this->assertSame('Unpaid', $invoice->status);
        $this->assertSame('REF-'.$invoice->code, $invoice->reference);
        $this->assertSame('2026-07-31', DB::table('orders')->where('idpel', $invoice->idpel)->value('expdate'));
        $this->assertDatabaseCount('report', 0);
    }

    public function test_explicit_bypass_confirms_active_gateway_invoice_and_late_callback_is_idempotent(): void
    {
        $invoice = $this->seedPayableInvoice('INV-BYPASS', 'CUST-BYPASS', '22-07-2026 10:00:00');
        $oldReference = $invoice->reference;
        $user = $this->financeUser();

        $response = $this->actingAs($user)->post('/admin/finance/invoice/update', [
            ...$this->paymentPayload($invoice),
            'bypass_gateway' => '1',
        ]);

        $response->assertRedirect('admin/finance/invoice')
            ->assertSessionHas('success', ['Berhasil mengupdate invoice #'.$invoice->code]);

        $invoice->refresh();
        $this->assertSame('Paid', $invoice->status);
        $this->assertSame(150000, (int) $invoice->received);
        $this->assertSame('CASH', $invoice->category);
        $this->assertSame('Tunai ( Bayar di kantor )', $invoice->method);
        $this->assertSame($user->nama, $invoice->update_by);
        $this->assertSame($oldReference, $invoice->reference);
        $this->assertSame('22-07-2026 10:00:00', $invoice->exppay);
        $this->assertSame('duitku', $invoice->provider);
        $this->assertNotNull($invoice->payment_url);
        $this->assertNotNull($invoice->qr_url);
        $this->assertSame(321, (int) $invoice->random_price);
        $this->assertSame('Active', DB::table('orders')->where('idpel', $invoice->idpel)->value('status'));
        $this->assertSame('2026-08-31', DB::table('orders')->where('idpel', $invoice->idpel)->value('expdate'));
        $this->assertDatabaseCount('report', 1);

        $callbackResult = (new TestableCallbackController)->commitForTest($invoice->code, [
            'provider_reference' => $oldReference,
            'amount' => '150000',
        ]);

        $this->assertFalse($callbackResult['committed']);
        $this->assertSame('Paid', $invoice->fresh()->status);
        $this->assertSame('2026-08-31', DB::table('orders')->where('idpel', $invoice->idpel)->value('expdate'));
        $this->assertDatabaseCount('report', 1);
    }

    public function test_expired_gateway_transaction_can_be_confirmed_without_bypass(): void
    {
        $invoice = $this->seedPayableInvoice('INV-EXPIRED-PAY', 'CUST-EXPIRED-PAY', '20-07-2026 10:00:00');

        $response = $this->actingAs($this->financeUser())
            ->post('/admin/finance/invoice/update', $this->paymentPayload($invoice));

        $response->assertRedirect('admin/finance/invoice')
            ->assertSessionHas('success', ['Berhasil mengupdate invoice #'.$invoice->code]);
        $this->assertSame('Paid', $invoice->fresh()->status);
        $this->assertDatabaseCount('report', 1);
    }

    public function test_advance_month_bypass_handles_active_source_and_destination_transactions(): void
    {
        $source = $this->seedPayableInvoice('INV-JULY', 'CUST-ADVANCE', '22-07-2026 10:00:00');
        $destination = $this->createInvoice(
            'INV-AUGUST',
            'CUST-ADVANCE',
            '23-07-2026 10:00:00',
            '2026-08-01',
            '2026-08-31'
        );

        $response = $this->actingAs($this->financeUser())->post('/admin/finance/invoice/update', [
            ...$this->paymentPayload($source),
            'confirmation_period' => 'next',
            'bypass_gateway' => '1',
        ]);

        $response->assertRedirect('admin/finance/invoice')
            ->assertSessionHas('success', [
                'Invoice #'.$source->code.' dibatalkan dan pembayaran dikonfirmasi ke invoice #'.$destination->code,
            ]);

        $source->refresh();
        $destination->refresh();
        $this->assertSame('Error', $source->status);
        $this->assertSame('REF-'.$source->code, $source->reference);
        $this->assertSame('Paid', $destination->status);
        $this->assertSame('REF-'.$destination->code, $destination->reference);
        $this->assertSame('2026-08-21', DB::table('orders')->where('idpel', $source->idpel)->value('expdate'));
        $this->assertDatabaseCount('report', 1);
    }

    private function seedPayableInvoice(string $code, string $idpel, string $expiresAt): Invoice
    {
        $invoice = $this->createInvoice($code, $idpel, $expiresAt);

        DB::table('orders')->insert([
            'idpel' => $idpel,
            'nama' => 'Customer '.$idpel,
            'paket' => 'Paket Test',
            'status' => 'Active',
            'date' => '2026-07-01',
            'expdate' => '2026-07-31',
        ]);
        DB::table('services')->insert([
            'paket' => 'Paket Test',
            'ppp_profile' => 'paket-test',
        ]);

        return $invoice;
    }

    private function createInvoice(
        string $code,
        string $idpel,
        string $expiresAt,
        string $date = '2026-07-01',
        string $expirationDate = '2026-07-31'
    ): Invoice {
        return Invoice::create([
            'code' => $code,
            'idpel' => $idpel,
            'nama' => 'Customer '.$idpel,
            'package' => 'Paket Test',
            'price' => 150000,
            'random_price' => 321,
            'received' => 0,
            'status' => 'Unpaid',
            'reference' => 'REF-'.$code,
            'date' => $date,
            'expdate' => $expirationDate,
            'exppay' => $expiresAt,
            'last_update' => $date,
            'payment_url' => 'https://payment.example/'.$code,
            'qr_url' => 'https://payment.example/qr/'.$code,
            'account' => 'user',
            'provider' => 'duitku',
        ]);
    }

    /** @return array<string, int|string> */
    private function paymentPayload(Invoice $invoice): array
    {
        return [
            'target' => $invoice->id,
            'code' => $invoice->code,
            'idpel' => $invoice->idpel,
            'status' => 'Paid',
            'category' => 'CASH',
            'metode' => 'Tunai ( Bayar di kantor )',
            'confirmation_period' => 'current',
            'upload_bukti' => 'tidak',
        ];
    }

    private function financeUser(): User
    {
        return User::create([
            'email' => 'finance-gateway@example.test',
            'nama' => 'Finance Gateway Test',
            'nomor' => '0800000000',
            'password' => bcrypt('password'),
            'balance' => 0,
            'level' => 'finance',
            'verify_account' => 1,
            'status_account' => 'Active',
        ]);
    }
}

class TestableCallbackController extends CallbackController
{
    /** @param array<string, string> $context */
    public function commitForTest(string $invoiceCode, array $context): array
    {
        return $this->commitGatewayPayment($invoiceCode, 'Duitku', $context);
    }
}
