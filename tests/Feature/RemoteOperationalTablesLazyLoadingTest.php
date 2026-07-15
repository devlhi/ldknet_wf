<?php

namespace Tests\Feature;

use App\Http\Controllers\Server\OltController;
use App\Http\Controllers\Server\RouterController;
use App\Libraries\HsgqAPI;
use App\Libraries\RouterosAPI;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

class RemoteOperationalTablesLazyLoadingTest extends TestCase
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
        Schema::create('router', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('dns');
            $table->string('ip');
            $table->string('username');
            $table->text('password');
            $table->string('interface');
        });
        Schema::create('olt', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('ip');
            $table->string('username');
            $table->text('password');
            $table->text('cookies')->nullable();
        });
        Schema::create('acs', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('url');
        });

        $this->user = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'level' => 'admin',
            'status_account' => 'Active',
        ]);
    }

    public function test_inventory_pages_do_not_load_row_collections_before_activation(): void
    {
        \DB::table('router')->insert([
            'nama' => 'Hidden Router', 'dns' => 'router.test', 'ip' => '192.0.2.1',
            'username' => 'admin', 'password' => legacy_encrypt('secret'), 'interface' => 'ether1',
        ]);
        \DB::table('olt')->insert([
            'nama' => 'Hidden OLT', 'ip' => 'http://olt.test', 'username' => 'admin',
            'password' => legacy_encrypt('secret'),
        ]);
        \DB::table('acs')->insert(['nama' => 'Hidden ACS', 'url' => 'http://acs.test']);
        Schema::create('bot_olt', function (Blueprint $table): void {
            $table->id();
            $table->string('command');
            $table->string('keterangan');
        });
        \DB::table('bot_olt')->insert(['command' => 'hidden-command', 'keterangan' => 'Hidden Bot']);

        $queries = [];
        \DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $pages = [
            '/server/router' => 'Hidden Router',
            '/server/olt' => 'Hidden OLT',
            '/server/olt/bot/whatsapp' => 'hidden-command',
            '/server/acs' => 'Hidden ACS',
        ];

        foreach ($pages as $page => $hiddenValue) {
            $response = $this->actingAs($this->user)->get($page);
            $response->assertOk()->assertSee('Tampilkan Data')->assertDontSee($hiddenValue);
        }

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression(
                '/\bfrom\s+[`"]?(router|olt|acs|bot_olt)[`"]?/i',
                $query,
                'Initial inventory GET unexpectedly queried a row collection: '.$query,
            );
        }

        foreach ($pages as $page => $hiddenValue) {
            $this->actingAs($this->user)->get($page.'?show_data=1')->assertOk()->assertSee($hiddenValue);
        }
    }

    public function test_remote_table_pages_do_not_call_remote_services_before_activation(): void
    {
        $routerId = \DB::table('router')->insertGetId([
            'nama' => 'Router Test', 'dns' => 'router.test', 'ip' => '192.0.2.1',
            'username' => 'admin', 'password' => legacy_encrypt('secret'), 'interface' => 'ether1',
        ]);

        $routerApi = Mockery::mock(RouterosAPI::class);
        $routerApi->shouldNotReceive('connect');
        $routerController = new RouterController;
        $this->replacePrivateProperty($routerController, 'ros', $routerApi);
        $this->app->instance(RouterController::class, $routerController);

        $oltApi = Mockery::mock(HsgqAPI::class);
        $oltApi->shouldNotReceive('getBoardInfo');
        $oltApi->shouldNotReceive('getOnuAllowList');
        $oltController = new OltController;
        $this->replacePrivateProperty($oltController, 'hsgqAPI', $oltApi);
        $this->app->instance(OltController::class, $oltController);

        $routerPages = [
            '/server/router/dashboard', '/server/router/hotspot/users',
            '/server/router/hotspot/profile', '/server/router/hotspot/active',
            '/server/router/hotspot/log', '/server/router/hotspot/host',
            '/server/router/ppp/profile', '/server/router/ppp/secret', '/server/router/ppp/active',
        ];

        foreach ($routerPages as $page) {
            $response = $this->actingAs($this->user)->withSession(['idrouter' => $routerId])->get($page);
            if ($response->getStatusCode() !== 200) {
                $this->fail("Page $page returned {$response->getStatusCode()} (redirect to: ".$response->headers->get('Location').')');
            }
            $response->assertSee('Tampilkan Data');
        }

        $this->actingAs($this->user)->withSession([
            'x-token' => 'token', 'host' => 'http://olt.test', 'namaolt' => 'OLT Test',
        ])->get('/server/olt/dashboard')->assertOk()->assertSee('Tampilkan Data');

        $this->actingAs($this->user)->withSession(['x-token' => 'token', 'host' => 'http://olt.test'])
            ->get('/server/olt/pon/1')->assertOk()->assertSee('Tampilkan Data');

        \DB::table('acs')->insert(['nama' => 'ACS Test', 'url' => 'http://acs.test']);
        $this->actingAs($this->user)->withSession(['idrouter' => 1])
            ->get('/server/acs/dashboard')->assertOk()->assertSee('Tampilkan Data');
    }

    public function test_show_data_activates_router_and_olt_row_fetches(): void
    {
        $routerId = \DB::table('router')->insertGetId([
            'nama' => 'Router Test', 'dns' => 'router.test', 'ip' => '192.0.2.1',
            'username' => 'admin', 'password' => legacy_encrypt('secret'), 'interface' => 'ether1',
        ]);
        $routerApi = Mockery::mock(RouterosAPI::class);
        $routerApi->shouldReceive('connect')->once()->andReturnTrue();
        $routerApi->shouldReceive('comm')->once()->with('/ip/hotspot/active/print')->andReturn([]);
        $routerController = new RouterController;
        $this->replacePrivateProperty($routerController, 'ros', $routerApi);
        $this->app->instance(RouterController::class, $routerController);

        $this->actingAs($this->user)->withSession(['idrouter' => $routerId])
            ->get('/server/router/hotspot/active?show_data=1')->assertOk()->assertDontSee('Data Hotspot Active belum dimuat');

        $oltApi = Mockery::mock(HsgqAPI::class);
        $oltApi->shouldReceive('getOnuAllowList')->once()->with('http://olt.test', '1', 'token')
            ->andReturn(json_encode(['data' => []]));
        $oltController = new OltController;
        $this->replacePrivateProperty($oltController, 'hsgqAPI', $oltApi);
        $this->app->instance(OltController::class, $oltController);

        $this->actingAs($this->user)->withSession(['x-token' => 'token', 'host' => 'http://olt.test'])
            ->get('/server/olt/pon/1?show_data=1')->assertOk()->assertDontSee('Data ONU belum dimuat');
    }

    private function replacePrivateProperty(object $target, string $property, object $value): void
    {
        $reflection = new ReflectionProperty($target, $property);
        $reflection->setValue($target, $value);
    }
}
