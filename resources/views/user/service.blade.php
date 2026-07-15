@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Layanan</h4>
                        <form method="GET" class="mb-3">
                            <button type="submit" name="show_data" value="1" class="btn btn-primary">Tampilkan Data</button>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Pelanggan</th>
                                        <th>Nama</th>
                                        <th>Paket</th>
                                        <th>Status</th>
                                        <th>Expired</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (! $showData)
                                        <tr><td colspan="5" class="text-center text-muted">Klik Tampilkan Data untuk memuat data.</td></tr>
                                    @else
                                        @forelse ($content as $row)
                                            <tr>
                                                <td>{{ $row->idpel }}</td>
                                                <td>{{ $row->nama }}</td>
                                                <td>{{ $row->package }}</td>
                                                <td>{{ $row->status }}</td>
                                                <td>{{ $row->expdate }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center">Data tidak ditemukan</td></tr>
                                        @endforelse
                                    @endif
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
