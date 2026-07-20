<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CoverageController;
use App\Libraries\ACSRequest;
use App\Models\GangguanReport;
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
            $table->timestamps();
        });
        Schema::create('coverage_odcs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->string('latitude');
            $table->string('longitude');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('coverage_odp_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odp_id')->unique();
            $table->unsignedBigInteger('coverage_odc_id');
            $table->timestamps();
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
        DB::table('coverage_odcs')->insert(['name' => 'ODC DINAMIS', 'code' => 'ODC-01', 'latitude' => '-0.11', 'longitude' => '109.22', 'description' => 'Cabang utara']);

        $odcs = $this->controller()->odc()->getData()['odcs'];
        $blade = file_get_contents(resource_path('views/admin/coverage/odc.blade.php'));

        $this->assertSame('ODC DINAMIS', $odcs->first()->name);
        $this->assertSame('Cabang utara', $odcs->first()->description);
        $this->assertStringContainsString('@json($odcs)', $blade);
        $this->assertStringNotContainsString('ODC 4', $blade);
    }

    public function test_odp_requires_odc_and_can_assign_existing_odp(): void
    {
        $odpId = DB::table('odp')->insertGetId(['nama' => 'ODP Lama', 'kode' => 'OLD', 'port' => 8]);
        $withoutOdc = $this->controller()->addodp(Request::create('/', 'POST', [
            'nama' => 'ODP Baru',
            'kode' => 'NEW',
            'jumlah' => 8,
        ]));
        $this->assertSame(1, DB::table('odp')->count());
        $this->assertNotEmpty($withoutOdc->getSession()->get('auth_errors'));

        $odcId = DB::table('coverage_odcs')->insertGetId(['name' => 'ODC Utama', 'code' => 'ODC-01', 'latitude' => '-0.1', 'longitude' => '109.1']);
        $this->controller()->updateODP(Request::create('/', 'POST', [
            'nama' => 'ODP Lama',
            'kode' => 'OLD',
            'jumlah' => 8,
            'odc_id' => $odcId,
        ]), $odpId);

        $this->assertDatabaseHas('coverage_odp_assignments', ['odp_id' => $odpId, 'coverage_odc_id' => $odcId]);
        $this->assertSame('ODC Utama', $this->controller()->odp(Request::create('/'))->getData()['data'][0]['odc_name']);
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

    public function test_odp_stats_match_legacy_truncated_names_case_insensitively(): void
    {
        DB::table('odp')->insert(['nama' => 'Gontang001(Mayam Heri)', 'kode' => 'L012', 'port' => 4]);
        DB::table('orders')->insert([
            ['nama' => 'One', 'nama_odp' => 'gontang001(maya', 'port_odp' => '1'],
            ['nama' => 'Two', 'nama_odp' => ' GONTANG001(MAYA ', 'port_odp' => '2'],
        ]);

        $data = $this->controller()->odp(Request::create('/'))->getData()['data'][0];

        $this->assertSame(2, $data['used_ports']);
        $this->assertSame([3, 4], $data['available_ports']);
        $this->assertSame(2, $data['pelanggan']);
    }

    public function test_odp_stats_do_not_guess_when_legacy_prefix_is_ambiguous(): void
    {
        DB::table('odp')->insert([
            ['nama' => 'Gontang001(Mayam Heri)', 'kode' => 'L012', 'port' => 4],
            ['nama' => 'Gontang001(Mayam Anton)', 'kode' => 'L013', 'port' => 4],
        ]);
        DB::table('orders')->insert(['nama' => 'Unknown', 'nama_odp' => 'Gontang001(Maya', 'port_odp' => '1']);

        $data = $this->controller()->odp(Request::create('/'))->getData()['data'];

        $this->assertSame([0, 0], array_column($data, 'pelanggan'));
    }

    public function test_odp_open_issue_count_matches_legacy_assignment_variants(): void
    {
        DB::table('odp')->insert(['nama' => 'Aping001(P.Hengin)', 'kode' => 'L008', 'port' => 8]);
        DB::table('gangguan_reports')->insert([
            ['nama_odp' => 'aping001(p.heng', 'status' => 'baru'],
            ['nama_odp' => ' APING001(P.HENG ', 'status' => 'diproses'],
            ['nama_odp' => 'Aping001(P.Hengin)', 'status' => 'selesai'],
        ]);

        $data = $this->controller()->odp(Request::create('/'))->getData()['data'][0];

        $this->assertSame(2, $data['gangguan_open']);
    }

    public function test_massal_alerts_resolve_legacy_variants_and_ignore_ambiguous_prefixes(): void
    {
        DB::table('odp')->insert([
            ['nama' => 'Aping001(P.Hengin)', 'kode' => 'L008', 'port' => 8, 'latitude' => '-0.1000', 'longitude' => '109.1000'],
            ['nama' => 'Gontang001(Mayam Heri)', 'kode' => 'L012', 'port' => 4, 'latitude' => '-0.2000', 'longitude' => '109.2000'],
            ['nama' => 'Gontang001(Mayam Anton)', 'kode' => 'L013', 'port' => 4, 'latitude' => '-0.3000', 'longitude' => '109.3000'],
        ]);
        DB::table('orders')->insert([
            ['nama' => 'One', 'status' => 'Active', 'nama_odp' => 'aping001(p.heng'],
            ['nama' => 'Two', 'status' => 'Active', 'nama_odp' => ' APING001(P.HENG '],
            ['nama' => 'Inactive', 'status' => 'Inactive', 'nama_odp' => 'aping001(p.heng'],
            ['nama' => 'Ambiguous', 'status' => 'Active', 'nama_odp' => 'gontang001(maya'],
        ]);
        DB::table('gangguan_reports')->insert([
            ['nama_odp' => 'aping001(p.heng', 'status' => 'baru', 'created_at' => now(), 'updated_at' => now()],
            ['nama_odp' => ' APING001(P.HENG ', 'status' => 'diproses', 'created_at' => now(), 'updated_at' => now()],
            ['nama_odp' => 'gontang001(maya', 'status' => 'baru', 'created_at' => now(), 'updated_at' => now()],
            ['nama_odp' => ' GONTANG001(MAYA ', 'status' => 'diproses', 'created_at' => now(), 'updated_at' => now()],
            ['nama_odp' => 'Gontang001(Mayam Heri)', 'status' => 'baru', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $alerts = GangguanReport::massalAlerts(2, 6);

        $this->assertCount(1, $alerts);
        $this->assertSame('Aping001(P.Hengin)', $alerts->first()->nama_odp);
        $this->assertSame(2, $alerts->first()->total);
        $this->assertSame(2, $alerts->first()->pelanggan_aktif);
        $this->assertSame('-0.1000', $alerts->first()->latitude);
        $this->assertSame('109.1000', $alerts->first()->longitude);
    }

    public function test_massal_alerts_prioritize_exact_full_name_when_legacy_prefix_is_ambiguous(): void
    {
        DB::table('odp')->insert([
            ['nama' => 'Gontang001(Mayam Heri)', 'kode' => 'L012', 'port' => 4],
            ['nama' => 'Gontang001(Mayam Anton)', 'kode' => 'L013', 'port' => 4],
        ]);
        DB::table('gangguan_reports')->insert([
            ['nama_odp' => 'Gontang001(Mayam Heri)', 'status' => 'baru', 'created_at' => now(), 'updated_at' => now()],
            ['nama_odp' => ' gontang001(mayam heri) ', 'status' => 'diproses', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $alerts = GangguanReport::massalAlerts(2, 6);

        $this->assertCount(1, $alerts);
        $this->assertSame('Gontang001(Mayam Heri)', $alerts->first()->nama_odp);
        $this->assertSame(2, $alerts->first()->total);
        $this->assertSame(0, $alerts->first()->pelanggan_aktif);
    }

    public function test_odp_rename_updates_dependencies_and_delete_is_protected(): void
    {
        $id = DB::table('odp')->insertGetId(['nama' => 'ODP LAMA', 'kode' => 'OLD', 'port' => 8]);
        DB::table('orders')->insert(['nama' => 'Customer', 'nama_odp' => 'ODP LAMA', 'port_odp' => '1']);
        DB::table('gangguan_reports')->insert(['nama_odp' => 'ODP LAMA', 'status' => 'baru']);

        $odcId = DB::table('coverage_odcs')->insertGetId(['name' => 'ODC Rename', 'latitude' => '-0.1', 'longitude' => '109.1']);
        $this->controller()->updateODP(Request::create('/', 'POST', ['nama' => 'ODP BARU', 'kode' => 'NEW', 'jumlah' => 8, 'odc_id' => $odcId]), $id);

        $this->assertDatabaseHas('orders', ['nama_odp' => 'ODP BARU']);
        $this->assertDatabaseHas('gangguan_reports', ['nama_odp' => 'ODP BARU']);
        $response = $this->controller()->deleteODP($id);
        $this->assertDatabaseHas('odp', ['id' => $id]);
        $this->assertStringContainsString('tidak dapat dihapus', implode(' ', $response->getSession()->get('auth_errors')));
    }

    public function test_long_odp_rename_updates_truncated_assignments_and_delete_remains_protected(): void
    {
        $id = DB::table('odp')->insertGetId(['nama' => 'Aping001(P.Hengin)', 'kode' => 'L008', 'port' => 8]);
        DB::table('orders')->insert(['nama' => 'Customer', 'nama_odp' => ' aping001(p.heng ', 'port_odp' => '1']);
        DB::table('gangguan_reports')->insert(['nama_odp' => ' APING001(P.HENG ', 'status' => 'baru']);
        $odcId = DB::table('coverage_odcs')->insertGetId(['name' => 'ODC Long', 'latitude' => '-0.1', 'longitude' => '109.1']);

        $this->controller()->updateODP(Request::create('/', 'POST', [
            'nama' => 'Aping001(Hengin Baru)',
            'kode' => 'L008',
            'jumlah' => 8,
            'odc_id' => $odcId,
        ]), $id);

        $this->assertDatabaseHas('orders', ['nama_odp' => 'Aping001(Hengin']);
        $this->assertDatabaseHas('gangguan_reports', ['nama_odp' => 'Aping001(Hengin Baru)']);
        $response = $this->controller()->deleteODP($id);
        $this->assertDatabaseHas('odp', ['id' => $id]);
        $this->assertStringContainsString('tidak dapat dihapus', implode(' ', $response->getSession()->get('auth_errors')));
    }

    public function test_odp_create_rejects_duplicate_legacy_storage_prefix(): void
    {
        DB::table('odp')->insert(['nama' => 'Gontang001(Mayam Heri)', 'kode' => 'L012', 'port' => 4]);
        $odcId = DB::table('coverage_odcs')->insertGetId(['name' => 'ODC Prefix', 'latitude' => '-0.1', 'longitude' => '109.1']);

        $response = $this->controller()->addodp(Request::create('/', 'POST', [
            'nama' => 'Gontang001(Mayam Anton)',
            'kode' => 'L013',
            'jumlah' => 4,
            'odc_id' => $odcId,
        ]));

        $this->assertSame(1, DB::table('odp')->count());
        $this->assertStringContainsString('15 karakter awal', implode(' ', $response->getSession()->get('auth_errors')));
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
        $odcId = DB::table('coverage_odcs')->insertGetId(['name' => 'ODC Cable', 'latitude' => '-0.1', 'longitude' => '109.1']);
        DB::table('coverage_odp_assignments')->insert(['odp_id' => $odpId, 'coverage_odc_id' => $odcId]);
        $path = [[-0.1, 109.1], [-0.2, 109.2]];

        $unknownOdp = $this->controller()->storeCable(Request::create('/', 'POST', [
            'odp_id' => 999,
            'path' => $path,
            'src_hash' => number_format((float) $odcId, 6, '.', '').'|-0.100000|109.100000|-0.200000|109.200000',
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
            'src_hash' => number_format((float) $odcId, 6, '.', '').'|-0.100000|109.100000|-0.200000|109.200000',
        ]));
        $this->assertSame(200, $stored->getStatusCode());
        $this->assertDatabaseHas('coverage_cables', ['odp_id' => $odpId]);
    }

    public function test_rx_power_uses_first_configured_acs_and_normalized_pppoe_username(): void
    {
        DB::table('acs')->insert(['nama' => 'ACS Utama', 'url' => 'http://acs.example.test']);
        DB::table('odp')->insert(['nama' => 'Aping001(P.Hengin)', 'kode' => 'L008', 'port' => 8]);
        DB::table('orders')->insert([
            'idpel' => 'C1',
            'nama' => 'Mapped',
            'status' => 'Active',
            'nama_odp' => 'aping001(p.heng',
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
        $this->assertSame('Aping001(P.Hengin)', $data['customers']->first()->nama_odp);
    }

    public function test_customer_map_payload_uses_only_needed_data_and_counts_coordinates(): void
    {
        DB::table('odp')->insert(['nama' => 'Aping001(P.Hengin)', 'kode' => 'L008', 'port' => 8, 'latitude' => '-0.1', 'longitude' => '109.1']);
        DB::table('orders')->insert([
            ['idpel' => 'C1', 'nama' => 'Mapped', 'status' => 'Active', 'nama_odp' => 'aping001(p.heng', 'port_odp' => '2', 'latitude' => '-0.2', 'longitude' => '109.2'],
            ['idpel' => 'C2', 'nama' => 'Unmapped', 'status' => 'Active', 'nama_odp' => null, 'port_odp' => null, 'latitude' => null, 'longitude' => null],
            ['idpel' => 'C3', 'nama' => 'Inactive', 'status' => 'Inactive', 'nama_odp' => null, 'port_odp' => null, 'latitude' => '-0.3', 'longitude' => '109.3'],
        ]);

        $data = $this->controller()->getCustomerMap(Request::create('/'))->getData();

        $this->assertCount(2, $data['customers']);
        $this->assertSame(1, $data['mappedCount']);
        $this->assertSame(1, $data['unmappedCount']);
        $this->assertSame(['idpel', 'nama', 'status', 'nama_odp', 'port_odp', 'latitude', 'longitude'], array_keys($data['customers']->first()->getAttributes()));
        $this->assertSame('Aping001(P.Hengin)', $data['customers']->first()->nama_odp);
    }

    private function controller(): CoverageController
    {
        return $this->app->make(CoverageController::class);
    }
}
