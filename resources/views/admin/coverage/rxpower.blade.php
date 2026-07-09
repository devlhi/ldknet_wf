@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
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
                                        <tr>
                                            <td>{{ $row->idpel }}</td>
                                            <td>{{ $row->nama }}</td>
                                            <td>{{ $row->pppoe_user }}</td>
                                            <td>{{ $row->nama_odp }}</td>
                                            <td>{{ $row->port_odp }}</td>
                                            <td>{{ $rxPowerData[$row->pppoe_user] ?? '-' }}</td>
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
