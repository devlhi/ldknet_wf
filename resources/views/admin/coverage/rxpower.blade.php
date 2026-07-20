@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
                        <p class="text-muted">RX Power diambil langsung dari server ACS dan dicocokkan berdasarkan username PPPoE.</p>
                        @if ($acsError)
                            <div class="alert alert-warning mb-3">{{ $acsError }}</div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Pelanggan</th>
                                        <th>Nama</th>
                                        <th>PPPoE User</th>
                                        <th>ODP</th>
                                        <th>Port</th>
                                        <th>RX Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customers as $row)
                                        @php
                                            $rxPower = $rxPowerData[\App\Services\AcsDeviceService::normalizePppoeUsername($row->pppoe_user) ?? ''] ?? null;
                                            $rxNumeric = is_numeric($rxPower) ? (float) $rxPower : null;
                                            $rxClass = $rxNumeric === null ? 'secondary' : (($rxNumeric > -8 || $rxNumeric <= -27) ? 'danger' : 'success');
                                        @endphp
                                        <tr>
                                            <td>{{ $row->idpel }}</td>
                                            <td>{{ $row->nama }}</td>
                                            <td>{{ $row->pppoe_user }}</td>
                                            <td>{{ $row->nama_odp ?: '-' }}</td>
                                            <td>{{ $row->port_odp ?: '-' }}</td>
                                            <td><span class="badge bg-{{ $rxClass }}">{{ $rxPower ?? 'Tidak ditemukan' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center">Data tidak ditemukan</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
