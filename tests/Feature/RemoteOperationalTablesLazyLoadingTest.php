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

    public function test_inventory_pages_eagerly_load_row_collections_without_activation_flags(): void
    {
        \DB::table('router')->insert([
            'nama' => 'Visible Router', 'dns' => 'router.test', 'ip' => '192.0.2.1',
            'username' => 'admin', 'password' => legacy_encrypt('secret'), 'interface' => 'ether1',
        ]);
        \DB::table('olt')->insert([
            'nama' => 'Visible OLT', 'ip' => 'http://olt.test', 'username' => 'admin',
            'password' => legacy_encrypt('secret'),
        ]);
        \DB::table('acs')->insert(['nama' => 'Visible ACS', 'url' => 'http://acs.test']);
        Schema::create('bot_olt', function (Blueprint $table): void {
            $table->id();
            $table->string('command');
            $table->string('keterangan');
        });
        \DB::table('bot_olt')->insert(['command' => 'visible-command', 'keterangan' => 'Visible Bot']);

        $pages = [
            '/server/router' => 'Visible Router',
            '/server/olt' => 'Visible OLT',
            '/server/olt/bot/whatsapp' => 'visible-command',
            '/server/acs' => 'Visible ACS',
        ];

        foreach ($pages as $page => $visibleValue) {
            $this->actingAs($this->user)->get($page)->assertOk()->assertSee($visibleValue);
        }
    }

    public function test_normal_requests_eagerly_fetch_remote_router_and_olt_rows_with_mocks(): void
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
            ->get('/server/router/hotspot/active')->assertOk();

        $oltApi = Mockery::mock(HsgqAPI::class);
        $oltApi->shouldReceive('getOnuAllowList')->once()->with('http://olt.test', '1', 'token')
            ->andReturn(json_encode(['data' => []]));
        $oltController = new OltController;
        $this->replacePrivateProperty($oltController, 'hsgqAPI', $oltApi);
        $this->app->instance(OltController::class, $oltController);

        $this->actingAs($this->user)->withSession(['x-token' => 'token', 'host' => 'http://olt.test'])
            ->get('/server/olt/pon/1')->assertOk();
    }

    private function replacePrivateProperty(object $target, string $property, object $value): void
    {
        $reflection = new ReflectionProperty($target, $property);
        $reflection->setValue($target, $value);
    }
}
