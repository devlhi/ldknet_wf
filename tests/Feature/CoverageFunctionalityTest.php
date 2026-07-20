<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CoverageController;
use App\Libraries\ACSRequest;
use App\Services\AcsDeviceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\DatabasePresenceVerifier;
use Tests\TestCase;

class CoverageFunctionalityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'coverage_testing');
        config()->set('database.connections.coverage_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('coverage_testing');
        DB::setDefaultConnection('coverage_testing');
        $this->app['validator']->setPresenceVerifier(new DatabasePresenceVerifier(DB::getFacadeRoot()));

        Schema::create('website', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
        });
        Schema::create('odp', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('nama')->unique();
            $table->integer('port');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('image')->nullable();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('idpel')->nullable();
            $table->string('nama')->nullable();
            $table->string('status')->nullable();
            $table->string('kode_area')->nullable();
            $table->string('nama_odp')->nullable();
            $table->string('port_odp')->nullable();
            $table->string('pppoe_user')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
        Schema::create('area', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
        });
        Schema::create('odc', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->unsignedBigInteger('olt_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('gangguan_reports', function (Blueprint $table) {
            $table->id();
            $table->string('nama_odp')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('coverage_cables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odp_id');
            $table->text('path')->nullable();
            $table->string('src_hash')->nullable();
            $table->timestamps();
        });
        Schema::create('coverage_map_setting', function (Blueprint $table) {
            $table->id();
            $table->string('hub_label')->nullable();
            $table->decimal('hub_lat', 10, 7)->nullable();
            $table->decimal('hub_lng', 10, 7)->nullable();
            $table->decimal('center_lat', 10, 7)->default(0.3);
            $table->decimal('center_lng', 10, 7)->default(109.5);
            $table->integer('zoom')->default(11);
            $table->string('basemap')->default('streets');
            $table->timestamps();
        });
        Schema::create('acs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('url');
        });
    }

    public function test_odc_renders_database_records_instead_of_hardcoded_markers(): void
    {
        DB::table('odc')->insert(['name' => 'ODC DINAMIS', 'latitude' => '-0.11', 'longitude' => '109.22', 'olt_id' => 7, 'description' => 'Cabang utara']);

        $odcs = $this->controller()->odc()->getData()['odcs'];
        $blade = file_get_contents(resource_path('views/admin/coverage/odc.blade.php'));

        $this->assertSame('ODC DINAMIS', $odcs->first()->name);
        $this->assertSame('Cabang utara', $odcs->first()->description);
        $this->assertStringContainsString('@json($odcs)', $blade);
        $this->assertStringNotContainsString('ODC 4', $blade);
    }

    public function test_odp_stats_normalize_and_deduplicate_ports(): void
    {
        DB::table('odp')->insert(['nama' => 'ODP-A', 'kode' => 'A', 'port' => 4]);
        DB::table('orders')->insert([
            ['nama' => 'One', 'nama_odp' => 'ODP-A', 'port_odp' => '01'],
            ['nama' => 'Two', 'nama_odp' => 'ODP-A', 'port_odp' => '1'],
            ['nama' => 'Three', 'nama_odp' => 'ODP-A', 'port_odp' => '3'],
            ['nama' => 'Invalid', 'nama_odp' => 'ODP-A', 'port_odp' => 'x'],
        ]);

        $data = $this->controller()->odp(Request::create('/'))->getData()['data'][0];

        $this->assertSame(2, $data['used_ports']);
        $this->assertSame([2, 4], $data['available_ports']);
        $this->assertSame(4, $data['pelanggan']);
    }

    public function test_odp_rename_updates_dependencies_and_delete_is_protected(): void
    {
        $id = DB::table('odp')->insertGetId(['nama' => 'ODP LAMA', 'kode' => 'OLD', 'port' => 8]);
        DB::table('orders')->insert(['nama' => 'Customer', 'nama_odp' => 'ODP LAMA', 'port_odp' => '1']);
        DB::table('gangguan_reports')->insert(['nama_odp' => 'ODP LAMA', 'status' => 'baru']);

        $this->controller()->updateODP(Request::create('/', 'POST', ['nama' => 'ODP BARU', 'kode' => 'NEW', 'jumlah' => 8]), $id);

        $this->assertDatabaseHas('orders', ['nama_odp' => 'ODP BARU']);
        $this->assertDatabaseHas('gangguan_reports', ['nama_odp' => 'ODP BARU']);
        $response = $this->controller()->deleteODP($id);
        $this->assertDatabaseHas('odp', ['id' => $id]);
        $this->assertStringContainsString('tidak dapat dihapus', implode(' ', $response->getSession()->get('auth_errors')));
    }

    public function test_area_validates_unique_code_renames_dependencies_and_protects_delete(): void
    {
        $first = DB::table('area')->insertGetId(['nama' => 'Area Satu', 'kode' => 'A1']);
        DB::table('area')->insert(['nama' => 'Area Dua', 'kode' => 'A2']);
        DB::table('orders')->insert(['nama' => 'Customer', 'kode_area' => 'A1']);

        $invalid = $this->controller()->areaAdd(Request::create('/', 'POST', ['area' => '', 'kode' => 'A2']));
        $this->assertNotEmpty($invalid->getSession()->get('auth_errors'));

        $this->controller()->updateArea(Request::create('/', 'POST', ['area' => 'Area Baru', 'kode' => 'B1']), $first);
        $this->assertDatabaseHas('orders', ['kode_area' => 'B1']);

        $this->controller()->deleteArea($first);
        $this->assertDatabaseHas('area', ['id' => $first]);
    }

    public function test_cable_cache_rejects_unknown_odp_and_stale_coordinate_hash(): void
    {
        DB::table('coverage_map_setting')->insert([
            'hub_lat' => '-0.1',
            'hub_lng' => '109.1',
            'center_lat' => '-0.1',
            'center_lng' => '109.1',
            'zoom' => 12,
            'basemap' => 'streets',
        ]);
        $odpId = DB::table('odp')->insertGetId([
            'nama' => 'ODP-A',
            'kode' => 'A',
            'port' => 8,
            'latitude' => '-0.2',
            'longitude' => '109.2',
        ]);
        $path = [[-0.1, 109.1], [-0.2, 109.2]];

        $unknownOdp = $this->controller()->storeCable(Request::create('/', 'POST', [
            'odp_id' => 999,
            'path' => $path,
            'src_hash' => '-0.100000|109.100000|-0.200000|109.200000',
        ]));
        $this->assertSame(422, $unknownOdp->getStatusCode());

        $staleHash = $this->controller()->storeCable(Request::create('/', 'POST', [
            'odp_id' => $odpId,
            'path' => $path,
            'src_hash' => 'stale',
        ]));
        $this->assertSame(409, $staleHash->getStatusCode());

        $stored = $this->controller()->storeCable(Request::create('/', 'POST', [
            'odp_id' => $odpId,
            'path' => $path,
            'src_hash' => '-0.100000|109.100000|-0.200000|109.200000',
        ]));
        $this->assertSame(200, $stored->getStatusCode());
        $this->assertDatabaseHas('coverage_cables', ['odp_id' => $odpId]);
    }

    public function test_rx_power_uses_first_configured_acs_and_normalized_pppoe_username(): void
    {
        DB::table('acs')->insert(['nama' => 'ACS Utama', 'url' => 'http://acs.example.test']);
        DB::table('orders')->insert([
            'idpel' => 'C1',
            'nama' => 'Mapped',
            'status' => 'Active',
            'nama_odp' => 'ODP-A',
            'port_odp' => '2',
        ]);

        $request = $this->createStub(ACSRequest::class);
        $request->method('getAllDevices')->willReturn([
            [
                'VirtualParameters' => [
                    'pppoeUsername' => ['_value' => 'CUST-ONE'],
                    'RXPower' => ['_value' => '-20.5'],
                ],
            ],
        ]);
        $service = new AcsDeviceService(fn (string $url): ACSRequest => $request);
        DB::table('orders')->where('idpel', 'C1')->update(['pppoe_user' => ' cust-one ']);

        $data = $this->controller()->rxpower(Request::create('/'), $service)->getData();

        $this->assertSame('-20.5', $data['rxPowerData']['cust-one']);
        $this->assertNull($data['acsError']);
        $this->assertCount(1, $data['customers']);
    }

    public function test_customer_map_payload_uses_only_needed_data_and_counts_coordinates(): void
    {
        DB::table('odp')->insert(['nama' => 'ODP-A', 'kode' => 'A', 'port' => 8, 'latitude' => '-0.1', 'longitude' => '109.1']);
        DB::table('orders')->insert([
            ['idpel' => 'C1', 'nama' => 'Mapped', 'status' => 'Active', 'nama_odp' => 'ODP-A', 'port_odp' => '2', 'latitude' => '-0.2', 'longitude' => '109.2'],
            ['idpel' => 'C2', 'nama' => 'Unmapped', 'status' => 'Active', 'nama_odp' => null, 'port_odp' => null, 'latitude' => null, 'longitude' => null],
            ['idpel' => 'C3', 'nama' => 'Inactive', 'status' => 'Inactive', 'nama_odp' => null, 'port_odp' => null, 'latitude' => '-0.3', 'longitude' => '109.3'],
        ]);

        $data = $this->controller()->getCustomerMap(Request::create('/'))->getData();

        $this->assertCount(2, $data['customers']);
        $this->assertSame(1, $data['mappedCount']);
        $this->assertSame(1, $data['unmappedCount']);
        $this->assertSame(['idpel', 'nama', 'status', 'nama_odp', 'port_odp', 'latitude', 'longitude'], array_keys($data['customers']->first()->getAttributes()));
        $this->assertStringContainsString('ODP-A', $data['customers']->first()->nama_odp);
    }

    private function controller(): CoverageController
    {
        return $this->app->make(CoverageController::class);
    }
}
