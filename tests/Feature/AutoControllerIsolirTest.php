<?php

namespace Tests\Feature;

use App\Http\Controllers\AutoController;
use App\Libraries\RouterosAPI;
use App\Models\Order;
use App\Models\Router;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AutoControllerIsolirTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('router', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama');
            $table->text('dns');
            $table->text('ip');
            $table->string('username');
            $table->string('password');
            $table->string('interface');
            $table->string('status');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('idpel');
            $table->string('email');
            $table->string('nama');
            $table->string('paket');
            $table->string('alamat');
            $table->string('nomor');
            $table->string('status');
            $table->date('date');
            $table->date('expdate');
            $table->text('pppoe_user');
            $table->string('id_router');
            $table->string('mode');
            $table->string('nama_odp');
            $table->string('port_odp');
        });
    }

    public function test_isolir_retries_and_succeeds_when_router_becomes_reachable(): void
    {
        // 1. Setup Router di DB
        $router = Router::create([
            'nama' => 'Mikrotik Test',
            'dns' => 'mikrotik.test',
            'ip' => '192.168.1.1',
            'username' => 'admin',
            'password' => legacy_encrypt('pass123'),
            'interface' => 'ether1',
            'status' => 'Active',
        ]);

        // 2. Setup Order/Pelanggan PPPoE berstatus Isolir
        $order = Order::create([
            'idpel' => 'LNET001',
            'email' => 'user@test.com',
            'nama' => 'Pelanggan PPPoE',
            'paket' => 'Home 10M',
            'alamat' => 'Jalan Test 1',
            'nomor' => '628123456789',
            'status' => 'Isolir',
            'date' => '2026-07-01',
            'expdate' => '2026-07-10',
            'pppoe_user' => 'user_pppoe',
            'id_router' => $router->id,
            'mode' => 'pppoe',
            'nama_odp' => 'ODP-1',
            'port_odp' => '1',
        ]);

        // 3. Mock RouterosAPI untuk simulasi GAGAL koneksi
        $mockRosFail = Mockery::mock(RouterosAPI::class);
        $mockRosFail->shouldReceive('connect')
            ->once()
            ->with($router->ip, $router->username, 'pass123')
            ->andReturn(false);
        $mockRosFail->shouldReceive('disconnect')
            ->once();

        // 4. Instansiasi Controller dan set mock fail
        $controller = new class($mockRosFail) extends AutoController
        {
            private $rosMock;

            public function __construct($mock)
            {
                $this->rosMock = $mock;
            }

            protected function makeRouteros(): RouterosAPI
            {
                return $this->rosMock;
            }
        };

        // Simpan log warning untuk asersi
        Log::shouldReceive('warning')
            ->once()
            ->with("Retry isolir ditunda, Mikrotik tidak terhubung (LNET001, router {$router->id})");

        // Jalankan isolir (simulasi hari pertama / gagal)
        ob_start();
        $controller->isolir();
        $output1 = ob_get_clean();

        $this->assertStringContainsString('Isolir: 1 dicoba, 0 berhasil, 1 akan dicoba lagi', $output1);

        // Pastikan status DB tetap 'Isolir' agar masuk antrean retry besoknya
        $order->refresh();
        $this->assertEquals('Isolir', $order->status);

        // 5. Mock RouterosAPI untuk simulasi SUKSES koneksi di hari berikutnya
        $mockRosSuccess = Mockery::mock(RouterosAPI::class);
        $mockRosSuccess->shouldReceive('connect')
            ->once()
            ->with($router->ip, $router->username, 'pass123')
            ->andReturn(true);
        $mockRosSuccess->shouldReceive('comm')
            ->once()
            ->with('/ppp/secret/set', ['numbers' => 'user_pppoe', 'profile' => 'isolir'])
            ->andReturn([]);
        $mockRosSuccess->shouldReceive('comm')
            ->once()
            ->with('/ppp/active/getall', ['.proplist' => '.id', '?name' => 'user_pppoe'])
            ->andReturn([['.id' => '*1']]);
        $mockRosSuccess->shouldReceive('comm')
            ->once()
            ->with('/ppp/active/remove', ['.id' => '*1'])
            ->andReturn([]);
        $mockRosSuccess->shouldReceive('disconnect')
            ->once();

        $controllerSuccess = new class($mockRosSuccess) extends AutoController
        {
            private $rosMock;

            public function __construct($mock)
            {
                $this->rosMock = $mock;
            }

            protected function makeRouteros(): RouterosAPI
            {
                return $this->rosMock;
            }
        };

        // Reset facade Log untuk simulasi hari berikutnya
        Log::spy();

        // Jalankan isolir kembali (simulasi hari berikutnya / sukses)
        ob_start();
        $controllerSuccess->isolir();
        $output2 = ob_get_clean();

        $this->assertStringContainsString('Isolir: 1 dicoba, 1 berhasil, 0 akan dicoba lagi', $output2);

        // Status di DB tetap 'Isolir' (hanya dilepas jika sudah dibayar)
        $order->refresh();
        $this->assertEquals('Isolir', $order->status);

        // Pembayaran mengubah order menjadi Active, sehingga tidak masuk retry lagi.
        $order->update(['status' => 'Active']);
        $controllerPaid = new class extends AutoController
        {
            protected function makeRouteros(): RouterosAPI
            {
                throw new \RuntimeException('Router tidak boleh dipanggil untuk pelanggan sudah bayar.');
            }
        };

        ob_start();
        $controllerPaid->isolir();
        $output3 = ob_get_clean();

        $this->assertStringContainsString('Isolir: 0 dicoba, 0 berhasil, 0 akan dicoba lagi', $output3);
    }
}
