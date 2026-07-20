<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Models\NmsDevice;
use App\Models\NmsLink;
use App\Models\NmsMetric;
use App\Models\NmsSlaSetting;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class NmsController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function index(Request $request)
    {
        $devices = NmsDevice::orderByDesc('id')->get();
        $links = NmsLink::with(['deviceA:id,nama,tipe,latitude,longitude', 'deviceB:id,nama,tipe,latitude,longitude'])->get();
        $monitorUrl = URL::signedRoute('nms.public.monitor');

        return view('admin.nms.index', [
            'title' => 'NMS Monitor',
            'devices' => $devices,
            'links' => $links,
            'monitorUrl' => $monitorUrl,
        ] + $this->websiteData());
    }

    public function slaReport(Request $request)
    {
        $period = $request->input('period', 'month');
        $now = Carbon::now();

        if ($period === 'today') {
            $startDate = $now->copy()->startOfDay();
            $periodLabel = 'Hari Ini ('.$now->format('d M Y').')';
        } elseif ($period === 'year') {
            $startDate = $now->copy()->startOfYear();
            $periodLabel = 'Tahun Ini ('.$now->format('Y').')';
        } else {
            $startDate = $now->copy()->startOfMonth();
            $periodLabel = 'Bulan Ini ('.$now->format('M Y').')';
        }

        $devices = NmsDevice::where('status', 'active')->orderBy('nama')->get();
        $slaSettings = NmsSlaSetting::whereIn('device_id', $devices->pluck('id'))->get()->keyBy('device_id');

        // Build query: for each device, determine if we count ping_status or link_status (specific interface)
        $slaData = [];
        foreach ($devices as $device) {
            $setting = $slaSettings->get($device->id);

            if ($setting && $setting->check_type === 'interface' && $setting->interface_name) {
                $interfaceNames = $setting->interface_name;
                if (! is_array($interfaceNames)) {
                    $interfaceNames = [$interfaceNames];
                }
                $query = NmsMetric::where('device_id', $device->id)
                    ->where('metric_type', 'link_status')
                    ->whereIn('port_name', $interfaceNames)
                    ->where('recorded_at', '>=', $startDate);
            } else {
                $query = NmsMetric::where('device_id', $device->id)
                    ->where('metric_type', 'ping_status')
                    ->where('recorded_at', '>=', $startDate);
            }

            $counts = $query->select('value', DB::raw('count(*) as total'))
                ->groupBy('value')
                ->pluck('total', 'value');

            $up = $counts->get('up', 0);
            $down = $counts->get('down', 0);
            $total = $up + $down;

            $slaPercentage = $total > 0 ? round(($up / $total) * 100, 2) : 100;
            $targetSla = $setting ? (float) $setting->target_sla : 99.50;
            $checkType = $setting ? $setting->check_type : 'ping';
            $interface = $setting?->interface_name;

            $slaData[] = [
                'id' => $device->id,
                'nama' => $device->nama,
                'ip' => $device->ip,
                'tipe' => strtoupper($device->tipe),
                'check_type' => $checkType,
                'interface' => is_array($interface) ? implode(', ', $interface) : $interface,
                'target_sla' => $targetSla,
                'up_count' => $up,
                'down_count' => $down,
                'total_checks' => $total,
                'sla' => $slaPercentage,
                'meets_target' => $slaPercentage >= $targetSla,
            ];
        }

        return view('admin.nms.sla', [
            'title' => 'SLA Report',
            'period' => $period,
            'periodLabel' => $periodLabel,
            'slaData' => collect($slaData)->sortBy('sla')->values(),
        ] + $this->websiteData());
    }

    public function slaSettingsForm($deviceId)
    {
        $device = NmsDevice::findOrFail($deviceId);
        $setting = NmsSlaSetting::where('device_id', $deviceId)->first();

        return view('admin.nms.sla-settings', [
            'title' => 'Pengaturan SLA: '.$device->nama,
            'device' => $device,
            'setting' => $setting,
        ] + $this->websiteData());
    }

    public function slaSettingsStore(Request $request, $deviceId)
    {
        $device = NmsDevice::findOrFail($deviceId);

        $validated = $request->validate([
            'check_type' => 'required|in:ping,interface',
            'interface_name' => 'nullable|array',
            'interface_name.*' => 'string|max:100',
            'target_sla' => 'required|numeric|min:0|max:100',
            'enabled' => 'boolean',
        ]);

        if ($validated['check_type'] === 'ping') {
            $validated['interface_name'] = null;
        }

        $validated['enabled'] = ($request->has('enabled'));
        $validated['device_id'] = $deviceId;

        NmsSlaSetting::updateOrCreate(
            ['device_id' => $deviceId],
            $validated
        );

        return redirect(url('admin/nms/sla'))->with('success', ['Pengaturan SLA untuk '.$device->nama.' berhasil disimpan']);
    }

    public function slaSettingsGlobalForm()
    {
        $devices = NmsDevice::where('status', 'active')->orderBy('nama')->get();
        $existingSettings = NmsSlaSetting::whereIn('device_id', $devices->pluck('id'))->get()->keyBy('device_id');

        return view('admin.nms.sla-global', [
            'title' => 'Pengaturan Global SLA',
            'devices' => $devices,
            'existingSettings' => $existingSettings,
        ] + $this->websiteData());
    }

    public function slaSettingsGlobalStore(Request $request)
    {
        $validated = $request->validate([
            'check_type' => 'required|in:ping,interface',
            'target_sla' => 'required|numeric|min:0|max:100',
            'enabled' => 'boolean',
            'devices' => 'required|array',
            'devices.*' => 'exists:nms_devices,id',
            'interface_name' => 'nullable|array',
            'interface_name.*' => 'nullable|array',
            'interface_name.*.*' => 'string|max:100',
        ]);

        $enabled = $request->has('enabled');
        $checkType = $validated['check_type'];
        $targetSla = $validated['target_sla'];
        $interfaceNames = $request->input('interface_name', []);

        foreach ($validated['devices'] as $deviceId) {
            $existing = NmsSlaSetting::where('device_id', $deviceId)->first();
            $interfaceName = null;

            if ($checkType === 'interface') {
                $interfaceName = $interfaceNames[$deviceId] ?? null;
                if (! $interfaceName || (is_array($interfaceName) && empty($interfaceName))) {
                    $interfaceName = $existing?->interface_name;
                }
            }

            NmsSlaSetting::updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'check_type' => $checkType,
                    'target_sla' => $targetSla,
                    'enabled' => $enabled,
                    'interface_name' => $interfaceName,
                ]
            );
        }

        $count = count($validated['devices']);

        return redirect(url('admin/nms/sla'))->with('success', ['Pengaturan Global SLA berhasil diterapkan ke '.$count.' device']);
    }

    public function mapData(Request $request)
    {
        $isPublic = ! auth()->check();

        if ($isPublic && ! $request->hasValidSignature()) {
            abort(403);
        }

        $devices = NmsDevice::select('id', 'nama', 'tipe', 'ip', 'port', 'latitude', 'longitude', 'lokasi', 'status')
            ->when($isPublic, fn ($query) => $query->where('status', 'active'))
            ->whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->get();

        $metricTypes = ['sfp_rx_power', 'sfp_tx_power', 'sfp_temperature', 'link_status', 'onu_count'];
        $latestMetricIds = NmsMetric::whereIn('device_id', $devices->pluck('id'))
            ->whereIn('metric_type', $metricTypes)
            ->selectRaw('MAX(id)')
            ->groupBy('device_id', 'port_name', 'metric_type');
        $metricsByDevice = NmsMetric::whereIn('id', $latestMetricIds)
            ->get()
            ->groupBy('device_id');

        $devices->each(function ($device) use ($metricsByDevice) {
            $latestMetrics = $metricsByDevice->get($device->id, collect())
                ->groupBy('port_name')
                ->map(fn ($ports) => $ports->keyBy('metric_type'))
                ->map(fn ($metrics) => [
                    'port_name' => $metrics->first()->port_name,
                    'rx_power' => $metrics->get('sfp_rx_power')?->value,
                    'tx_power' => $metrics->get('sfp_tx_power')?->value,
                    'temperature' => $metrics->get('sfp_temperature')?->value,
                    'link_status' => $metrics->get('link_status')?->value,
                    'onu_count' => $metrics->get('onu_count')?->value,
                ])
                ->values();

            $device->sfp_ports = $latestMetrics->filter(fn ($p) => $p['rx_power'] !== null || $p['tx_power'] !== null || $p['onu_count'] !== null)->values();
        });

        $links = NmsLink::with(['deviceA:id,nama,latitude,longitude', 'deviceB:id,nama,latitude,longitude'])
            ->where('status', 'active')
            ->get()
            ->filter(fn ($link) => $link->deviceA && $link->deviceB && $link->deviceA->latitude && $link->deviceB->latitude)
            ->values();

        return response()->json([
            'data' => $devices,
            'links' => $links,
        ]);
    }

    public function deviceAddForm()
    {
        return view('admin.nms.form', [
            'title' => 'Tambah Device NMS',
            'device' => null,
        ] + $this->websiteData());
    }

    public function deviceEditForm($id)
    {
        $device = NmsDevice::findOrFail($id);

        return view('admin.nms.form', [
            'title' => 'Edit Device NMS',
            'device' => $device,
        ] + $this->websiteData());
    }

    public function deviceStore(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|in:mikrotik,crs,olt,snmp',
            'ip' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:100',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'lokasi' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = legacy_encrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        NmsDevice::create($validated);

        return redirect(url('admin/nms'))->with('success', ['Device berhasil ditambahkan']);
    }

    public function deviceUpdate(Request $request, $id)
    {
        $device = NmsDevice::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'tipe' => 'required|in:mikrotik,crs,olt,snmp',
            'ip' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:100',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'lokasi' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = legacy_encrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $device->update($validated);

        return redirect(url('admin/nms'))->with('success', ['Device berhasil diupdate']);
    }

    public function deviceDelete($id)
    {
        NmsDevice::findOrFail($id)->delete();

        return redirect(url('admin/nms'))->with('success', ['Device berhasil dihapus']);
    }

    public function deviceDetail($id)
    {
        $device = NmsDevice::findOrFail($id);

        return view('admin.nms.detail', [
            'title' => 'Detail Device: '.$device->nama,
            'device' => $device,
        ] + $this->websiteData());
    }

    public function poll($id)
    {

        $device = NmsDevice::findOrFail($id);

        if (in_array($device->tipe, ['mikrotik', 'crs'])) {
            return $this->pollMikrotik($device);
        }

        if ($device->tipe === 'snmp') {
            return $this->pollSnmp($device);
        }

        if ($device->tipe === 'olt') {
            return $this->pollOlt($device);
        }

        return response()->json(['error' => 'Tipe device tidak didukung'], 400);
    }

    private function pollMikrotik(NmsDevice $device)
    {
        $api = new RouterosAPI;
        $api->port = (int) $device->port;
        $api->timeout = 5;
        $api->attempts = 1;

        $password = $device->password ? legacy_decrypt($device->password) : '';

        if (! $api->connect($device->ip, $device->username ?? '', $password)) {
            return response()->json(['error' => 'Tidak dapat terhubung ke device', 'ports' => []], 200);
        }

        $interfaces = $api->comm('/interface/print');

        if (isset($interfaces['!trap']) || empty($interfaces)) {
            $api->disconnect();

            return response()->json(['error' => 'Tidak dapat membaca interface', 'ports' => []], 200);
        }

        $ports = [];

        foreach ($interfaces as $iface) {
            $name = $iface['name'] ?? '';
            if ($name === '') {
                continue;
            }
            $running = ($iface['running'] ?? '') === 'true';
            $disabled = ($iface['disabled'] ?? '') === 'true';

            $portData = [
                'name' => $name,
                'type' => $iface['type'] ?? '',
                'running' => $running,
                'disabled' => $disabled,
                'rx_power' => null,
                'tx_power' => null,
                'link_status' => $disabled ? 'disabled' : ($running ? 'up' : 'down'),
            ];

            $monitor = $api->comm('/interface/ethernet/monitor', ['once' => '', 'numbers' => $name]);

            if (! empty($monitor) && ! isset($monitor['!trap']) && ! isset($monitor['!done'])) {
                $mon = $monitor[0] ?? (is_array($monitor) ? $monitor : []);
                if (! empty($mon)) {
                    $portData['rx_power'] = $mon['sfp-rx-power'] ?? ($mon['rx-power'] ?? null);
                    $portData['tx_power'] = $mon['sfp-tx-power'] ?? ($mon['tx-power'] ?? null);
                    $portData['link_status'] = $mon['link'] ?? $portData['link_status'];
                    $portData['rate'] = $mon['rate'] ?? null;
                    $portData['auto_negotiation'] = $mon['auto-negotiation'] ?? null;
                    $portData['sfp_temperature'] = $mon['sfp-temperature'] ?? null;
                    $portData['sfp_vendor'] = $mon['sfp-vendor-name'] ?? null;
                    $portData['sfp_model'] = $mon['sfp-vendor-part-number'] ?? null;
                }
            }

            $ports[] = $portData;

            $this->saveMetric($device->id, $name, 'link_status', $portData['link_status'], 'status');
            if ($portData['rx_power'] !== null) {
                $this->saveMetric($device->id, $name, 'sfp_rx_power', (string) $portData['rx_power'], 'dBm');
            }
            if ($portData['tx_power'] !== null) {
                $this->saveMetric($device->id, $name, 'sfp_tx_power', (string) $portData['tx_power'], 'dBm');
            }
        }

        $resource = $api->comm('/system/resource/print');
        $resource = is_array($resource) && ! isset($resource['!trap']) ? ($resource[0] ?? $resource) : [];
        $routerboard = $api->comm('/system/routerboard/print');
        $routerboard = is_array($routerboard) && ! isset($routerboard['!trap']) ? ($routerboard[0] ?? $routerboard) : [];

        $api->disconnect();

        return response()->json([
            'device' => [
                'nama' => $device->nama,
                'ip' => $device->ip,
                'tipe' => $device->tipe,
                'uptime' => $resource['uptime'] ?? '-',
                'cpu_load' => $resource['cpu-load'] ?? '-',
                'memory_used' => isset($resource['total-memory'], $resource['free-memory'])
                    ? round((1 - $resource['free-memory'] / $resource['total-memory']) * 100, 1).'%'
                    : '-',
                'free_hdd' => $resource['free-hdd-space'] ?? '-',
                'routerboard_model' => $routerboard['model'] ?? '-',
                'serial_number' => $routerboard['serial-number'] ?? '-',
                'ros_version' => $resource['version'] ?? '-',
            ],
            'ports' => $ports,
        ]);
    }

    private function pollSnmp(NmsDevice $device)
    {
        if (! function_exists('snmp2_real_walk')) {
            return response()->json(['error' => 'PHP SNMP extension tidak tersedia', 'ports' => []], 200);
        }

        $community = $device->community ?: 'public';
        $ports = [];

        $ifDescr = @snmp2_real_walk($device->ip, $community, '.1.3.6.1.2.1.2.2.1.2');
        $ifOperStatus = @snmp2_real_walk($device->ip, $community, '.1.3.6.1.2.1.2.2.1.8');

        if (! $ifDescr) {
            return response()->json(['error' => 'Tidak dapat terhubung via SNMP', 'ports' => []], 200);
        }

        foreach ($ifDescr as $oid => $name) {
            $name = trim($name, '"');
            $oidNum = str_replace('.1.3.6.1.2.1.2.2.1.2.', '', $oid);
            $status = $ifOperStatus['.1.3.6.1.2.1.2.2.1.8.'.$oidNum] ?? null;
            $statusText = 'unknown';
            if ($status !== null) {
                $statusText = str_contains(strtolower($status), 'up') ? 'up' : 'down';
            }

            $ports[] = [
                'name' => $name,
                'type' => 'snmp',
                'running' => $statusText === 'up',
                'disabled' => false,
                'rx_power' => null,
                'tx_power' => null,
                'link_status' => $statusText,
            ];

            $this->saveMetric($device->id, $name, 'link_status', $statusText, 'status');
        }

        return response()->json([
            'device' => [
                'nama' => $device->nama,
                'ip' => $device->ip,
                'tipe' => 'snmp',
            ],
            'ports' => $ports,
        ]);
    }

    private function pollOlt(NmsDevice $device)
    {
        if (! function_exists('snmp2_real_walk') && ! function_exists('snmprealwalk')) {
            return response()->json([
                'error' => 'PHP SNMP extension tidak tersedia. Install dengan: apt install php-snmp (Linux) atau enable di php.ini (Windows).',
                'ports' => [],
            ], 200);
        }

        $community = $device->community ?: 'public';
        $ip = $device->ip;
        $timeout = 5;
        $retries = 1;

        // C-DATA FD-OLT-MIB OID tree: enterprises.34592 -> eponeoc(1) -> ipProduct(3) -> epon(3) -> fdOlt(3)
        $oidBase = '.1.3.6.1.4.1.34592.3.3';
        $oidOltBaseManage = $oidBase.'.1.1';   // oltBaseManageEntry
        $oidOltDdm = $oidBase.'.4.5.1';        // oltDdmInfoEntry
        $oidOnuStatus = $oidBase.'.14.1.1';     // onuStatusBmp

        // Standard SNMP MIB-II interface OIDs
        $oidIfDescr = '.1.3.6.1.2.1.2.2.1.2';
        $oidIfOperStatus = '.1.3.6.1.2.1.2.2.1.8';

        // 1. Walk OLT base management info
        $oltBaseWalk = @snmp2_real_walk($ip, $community, $oidOltBaseManage, $timeout, $retries);
        if ($oltBaseWalk === false) {
            return response()->json(['error' => 'Tidak dapat terhubung ke OLT via SNMP. Periksa IP, community, dan port 161.', 'ports' => []], 200);
        }

        $oltMacAddr = $this->extractSnmpValue($oltBaseWalk, $oidOltBaseManage.'.1');
        $oltWorkState = $this->extractSnmpValue($oltBaseWalk, $oidOltBaseManage.'.2');
        $accessedOnuNumber = $this->extractSnmpValue($oltBaseWalk, $oidOltBaseManage.'.7');

        // 2. Walk OLT DDM info (SFP optical diagnostics)
        $ddmWalk = @snmp2_real_walk($ip, $community, $oidOltDdm, $timeout, $retries);
        $ddmPorts = [];

        if ($ddmWalk !== false) {
            // Group DDM values by port index (last OID segment before the column)
            $ddmData = [];
            foreach ($ddmWalk as $fullOid => $rawValue) {
                // OID format: .1.3.6.1.4.1.34592.3.3.4.5.1.<column>.<portIndex>
                $oidParts = explode('.', $fullOid);
                $numParts = count($oidParts);
                if ($numParts < 2) {
                    continue;
                }
                $portIndex = $oidParts[$numParts - 1];
                $column = $oidParts[$numParts - 2];
                $ddmData[$portIndex][$column] = $this->cleanSnmpValue($rawValue);
            }

            foreach ($ddmData as $portIdx => $columns) {
                $portName = 'PON-'.$portIdx;
                $temperature = isset($columns['1']) ? $this->scaleDdmValue($columns['1'], 0.01, 'C') : null;
                $voltage = isset($columns['2']) ? $this->scaleDdmValue($columns['2'], 0.01, 'V') : null;
                $txBias = isset($columns['3']) ? $columns['3'] : null;
                $txPower = isset($columns['4']) ? $this->convertDdmPower($columns['4']) : null;
                $rxPower = isset($columns['5']) ? $this->convertDdmPower($columns['5']) : null;
                $vendor = isset($columns['6']) ? trim($columns['6'], '"') : null;
                $productName = isset($columns['7']) ? trim($columns['7'], '"') : null;
                $sn = isset($columns['9']) ? trim($columns['9'], '"') : null;

                $ddmPorts[$portIdx] = [
                    'name' => $portName,
                    'type' => 'pon-sfp',
                    'running' => $rxPower !== null,
                    'disabled' => false,
                    'rx_power' => $rxPower,
                    'tx_power' => $txPower,
                    'link_status' => $rxPower !== null ? 'up' : 'down',
                    'sfp_temperature' => $temperature,
                    'sfp_voltage' => $voltage,
                    'sfp_tx_bias' => $txBias,
                    'sfp_vendor' => $vendor,
                    'sfp_model' => $productName,
                    'sfp_sn' => $sn,
                ];

                // Save metrics
                $this->saveMetric($device->id, $portName, 'link_status', $rxPower !== null ? 'up' : 'down', 'status');
                if ($rxPower !== null) {
                    $this->saveMetric($device->id, $portName, 'sfp_rx_power', (string) $rxPower, 'dBm');
                }
                if ($txPower !== null) {
                    $this->saveMetric($device->id, $portName, 'sfp_tx_power', (string) $txPower, 'dBm');
                }
                if ($temperature !== null) {
                    $this->saveMetric($device->id, $portName, 'sfp_temperature', (string) $temperature, 'C');
                }
                if ($voltage !== null) {
                    $this->saveMetric($device->id, $portName, 'sfp_voltage', (string) $voltage, 'V');
                }
                if ($txBias !== null) {
                    $this->saveMetric($device->id, $portName, 'sfp_tx_bias', (string) $txBias, 'mA');
                }
            }
        }

        // 3. Walk standard interface table for uplink ports
        $ifDescr = @snmp2_real_walk($ip, $community, $oidIfDescr, $timeout, $retries);
        $ifOperStatus = @snmp2_real_walk($ip, $community, $oidIfOperStatus, $timeout, $retries);
        $uplinkPorts = [];

        if ($ifDescr !== false) {
            foreach ($ifDescr as $oid => $rawName) {
                $name = trim($this->cleanSnmpValue($rawName), '"');
                if ($name === '') {
                    continue;
                }

                $oidNum = str_replace($oidIfDescr.'.', '', $oid);
                $rawStatus = $ifOperStatus[$oidIfOperStatus.'.'.$oidNum] ?? null;
                $statusText = $this->parseIfOperStatus($rawStatus);

                // Skip PON ports (already handled by DDM), only show uplink/GE ports
                if (preg_match('/^(pon|epon|gpon)/i', $name)) {
                    continue;
                }

                $uplinkPorts[] = [
                    'name' => $name,
                    'type' => 'uplink',
                    'running' => $statusText === 'up',
                    'disabled' => false,
                    'rx_power' => null,
                    'tx_power' => null,
                    'link_status' => $statusText,
                    'sfp_temperature' => null,
                    'sfp_vendor' => null,
                ];

                $this->saveMetric($device->id, $name, 'link_status', $statusText, 'status');
            }
        }

        // 4. Walk ONU status bitmap for per-PON-port ONU online count
        $onuStatusWalk = @snmp2_real_walk($ip, $community, $oidOnuStatus, $timeout, $retries);
        $onuCounts = [];

        if ($onuStatusWalk !== false) {
            foreach ($onuStatusWalk as $oid => $rawValue) {
                $oidParts = explode('.', $oid);
                $portIndex = end($oidParts);
                $bmp = $this->cleanSnmpValue($rawValue);
                $onlineCount = $this->countOnuBitmap($bmp);

                $onuCounts[$portIndex] = $onlineCount;

                $ponName = 'PON-'.$portIndex;
                $this->saveMetric($device->id, $ponName, 'onu_count', (string) $onlineCount, 'count');
            }
        }

        // Merge ONU counts into DDM ports
        foreach ($ddmPorts as $idx => &$port) {
            if (isset($onuCounts[$idx])) {
                $port['onu_online'] = $onuCounts[$idx];
            }
        }
        unset($port);

        // Combine all ports: PON SFP ports + uplink ports
        $allPorts = array_merge(array_values($ddmPorts), $uplinkPorts);

        // Build device info
        $deviceInfo = [
            'nama' => $device->nama,
            'ip' => $device->ip,
            'tipe' => 'olt',
            'mac_address' => $oltMacAddr,
            'work_state' => $oltWorkState,
            'onu_total' => $accessedOnuNumber,
            'uptime' => '-',
            'cpu_load' => '-',
            'memory_used' => '-',
            'ros_version' => '-',
            'routerboard_model' => 'C-DATA OLT',
        ];

        // Try to get system uptime via sysUpTime
        $sysUpTime = @snmp2_get($ip, $community, '.1.3.6.1.2.1.1.3.0', $timeout, $retries);
        if ($sysUpTime !== false) {
            $deviceInfo['uptime'] = $this->formatSnmpTimeticks($this->cleanSnmpValue($sysUpTime));
        }

        // Try to get system description
        $sysDescr = @snmp2_get($ip, $community, '.1.3.6.1.2.1.1.1.0', $timeout, $retries);
        if ($sysDescr !== false) {
            $desc = trim($this->cleanSnmpValue($sysDescr), '"');
            if ($desc !== '') {
                $deviceInfo['routerboard_model'] = $desc;
            }
        }

        return response()->json([
            'device' => $deviceInfo,
            'ports' => $allPorts,
        ]);
    }

    private function extractSnmpValue(array $walkResult, string $targetOid): ?string
    {
        foreach ($walkResult as $oid => $value) {
            if ($oid === $targetOid || str_ends_with($oid, '.'.substr($targetOid, strrpos($targetOid, '.') + 1))) {
                return $this->cleanSnmpValue($value);
            }
        }

        return null;
    }

    private function cleanSnmpValue(string $raw): string
    {
        $val = trim($raw);
        // Remove SNMP type prefixes: INTEGER:, STRING:, Hex-STRING:, Gauge32:, Counter32:, etc.
        if (preg_match('/^(INTEGER|STRING|Hex-STRING|Gauge32|Counter32|Counter64|INTEGER32|Unsigned32|IpAddress|Timeticks|OID|BITS):\s*(.*)$/i', $val, $m)) {
            $val = trim($m[2]);
        }
        // Remove surrounding quotes
        if (strlen($val) >= 2 && $val[0] === '"' && substr($val, -1) === '"') {
            $val = substr($val, 1, -1);
        }

        return trim($val);
    }

    private function scaleDdmValue(string $value, float $factor, string $unit): ?string
    {
        $num = (float) $value;
        if ($num == 0 && $unit !== 'C') {
            return null;
        }
        $scaled = $num * $factor;

        return number_format($scaled, 2, '.', '').' '.$unit;
    }

    private function convertDdmPower(string $value): ?string
    {
        // C-DATA OLT reports DDM power in 0.01 dBm units (unsigned/signed integer)
        $num = (float) $value;
        if ($num == 0) {
            return null;
        }
        $dbm = $num * 0.01;

        return number_format($dbm, 2, '.', '');
    }

    private function parseIfOperStatus(?string $raw): string
    {
        if ($raw === null) {
            return 'unknown';
        }
        $val = strtolower($this->cleanSnmpValue($raw));

        return match (true) {
            str_contains($val, 'up') => 'up',
            str_contains($val, 'down') => 'down',
            str_contains($val, 'testing') => 'testing',
            default => 'unknown',
        };
    }

    private function countOnuBitmap(string $bmp): int
    {
        // ONU status bitmap is a hex string; count set bits (1 = online)
        $hex = $this->cleanSnmpValue($bmp);
        // Remove any spaces from hex string
        $hex = str_replace(' ', '', $hex);
        $count = 0;
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $nibble = hexdec($hex[$i]);
            $count += substr_count(decbin($nibble), '1');
        }

        return $count;
    }

    private function formatSnmpTimeticks(string $value): string
    {
        // Timeticks format: (12345) 1:02:03.04 or just a number
        if (preg_match('/\((\d+)\)/', $value, $m)) {
            $ticks = (int) $m[1];
        } else {
            $ticks = (int) $value;
        }
        $seconds = intdiv($ticks, 100);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($days > 0) {
            return $days.'d '.$hours.'h '.$mins.'m';
        }

        return $hours.'h '.$mins.'m '.$secs.'s';
    }

    public function metricsHistory($id, $port)
    {

        $metrics = NmsMetric::where('device_id', $id)
            ->where('port_name', $port)
            ->orderByDesc('recorded_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json(['data' => $metrics]);
    }

    public function checkStatus($id)
    {

        $device = NmsDevice::findOrFail($id);
        $port = (int) $device->port;
        $connected = @fsockopen($device->ip, $port, $errno, $errstr, 2);

        $statusText = $connected ? 'up' : 'down';

        // Simpan history ping_status ke metrics
        $this->saveMetric($device->id, 'ping', 'ping_status', $statusText, 'status');

        if ($connected) {
            fclose($connected);

            return response()->json(['status' => 'up']);
        }

        return response()->json(['status' => 'down']);
    }

    private function saveMetric(int $deviceId, string $portName, string $metricType, string $value, string $unit): void
    {
        if ($value === '') {
            return;
        }

        NmsMetric::create([
            'device_id' => $deviceId,
            'port_name' => $portName,
            'metric_type' => $metricType,
            'value' => $value,
            'unit' => $unit,
        ]);
    }

    // ---- Link CRUD ----

    public function linkStore(Request $request)
    {
        $validated = $request->validate([
            'device_a_id' => 'required|exists:nms_devices,id|different:device_b_id',
            'device_b_id' => 'required|exists:nms_devices,id',
            'port_a' => 'nullable|string|max:50',
            'port_b' => 'nullable|string|max:50',
            'label' => 'nullable|string|max:100',
            'link_type' => 'required|in:fiber,wireless,copper',
            'status' => 'required|in:active,inactive',
        ]);

        NmsLink::create($validated);

        return redirect(url('admin/nms'))->with('success', ['Link fiber berhasil ditambahkan']);
    }

    public function linkDelete($id)
    {
        NmsLink::findOrFail($id)->delete();

        return redirect(url('admin/nms'))->with('success', ['Link fiber berhasil dihapus']);
    }

    // ---- Public Monitor (no session, signed URL) ----

    public function publicMonitor(Request $request)
    {
        $devices = NmsDevice::select('id', 'nama', 'tipe', 'ip', 'port', 'latitude', 'longitude', 'lokasi', 'status')
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->get();

        $links = NmsLink::with(['deviceA:id,nama,latitude,longitude', 'deviceB:id,nama,latitude,longitude'])
            ->where('status', 'active')
            ->get()
            ->filter(fn ($link) => $link->deviceA && $link->deviceB && $link->deviceA->latitude && $link->deviceB->latitude)
            ->values();

        $website = Website::first();

        return view('admin.nms.public', [
            'title' => 'Network Monitor',
            'devices' => $devices,
            'links' => $links,
            'mapDataUrl' => URL::signedRoute('nms.public.map-data'),
            'statusUrls' => $devices->mapWithKeys(fn ($device) => [
                $device->id => URL::signedRoute('nms.public.device-status', ['id' => $device->id]),
            ]),
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ]);
    }

    public function publicMapData(Request $request)
    {
        return $this->mapData($request);
    }

    public function publicDeviceStatus($id)
    {
        $device = NmsDevice::where('status', 'active')->findOrFail($id);
        $latestStatus = NmsMetric::where('device_id', $device->id)
            ->where('metric_type', 'ping_status')
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->value('value');

        return response()->json(['status' => $latestStatus === 'up' ? 'up' : 'down']);
    }
}
