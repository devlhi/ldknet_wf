@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Riwayat Absensi</h4>
                        <a href="{{ url('karyawan/absensi') }}" class="btn btn-primary"><i class="uil uil-arrow-left me-1"></i> Kembali</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <button type="submit" name="show_data" value="1" class="btn btn-primary"><i class="uil uil-eye me-1"></i> Tampilkan Data</button>
                    </form>
                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (! $showData)
                                <tr><td colspan="6" class="text-center text-muted">Klik Tampilkan Data untuk memuat data.</td></tr>
                            @else
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ optional($row->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ optional($row->check_in)->format('H:i') ?? '-' }}</td>
                                        <td>{{ optional($row->check_out)->format('H:i') ?? '-' }}</td>
                                        <td><span class="badge bg-info text-capitalize">{{ $row->status }}</span></td>
                                        <td>{{ $row->keterangan }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
