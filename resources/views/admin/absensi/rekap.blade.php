@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Rekap Absensi</h4>
                        <a href="{{ url('admin/absensi/rekap/export?'.http_build_query(['start' => $filters['start'], 'end' => $filters['end'], 'user_id' => $filters['userId'], 'status' => $filters['status']])) }}" class="btn btn-success">
                            <i class="uil uil-file-download-alt me-1"></i> Export CSV
                        </a>
                    </div>
                </div>
            </div>

            @if (session('auth_errors'))
                <div class="alert alert-danger" role="alert">
                    @foreach (session('auth_errors') as $err)
                        {{ $err }}
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>
                    @foreach (session('success') as $suc)
                        {{ $suc }}
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                </div>
            @endif

            <div class="row">
                @foreach (['hadir' => 'success', 'izin' => 'info', 'sakit' => 'warning', 'alpha' => 'danger', 'cuti' => 'secondary'] as $key => $color)
                    <div class="col">
                        <div class="card">
                            <div class="card-body py-3 text-center">
                                <h3 class="mb-0 text-{{ $color }}">{{ $summary[$key] }}</h3>
                                <p class="text-muted mb-0 text-capitalize">{{ $key }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ url('admin/absensi/rekap') }}" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start" class="form-control" value="{{ $filters['start'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end" class="form-control" value="{{ $filters['end'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Karyawan</label>
                            <select name="user_id" class="form-select">
                                <option value="">Semua</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ (string) $filters['userId'] === (string) $emp->id ? 'selected' : '' }}>{{ $emp->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua</option>
                                @foreach (['hadir', 'izin', 'sakit', 'alpha', 'cuti'] as $st)
                                    <option value="{{ $st }}" {{ $filters['status'] === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="uil uil-filter me-1"></i> Filter</button>
                            <a href="{{ url('admin/absensi/rekap') }}" class="btn btn-light">Reset</a>
                        </div>
                    </form>

                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ optional($row->tanggal)->format('d-m-Y') }}</td>
                                    <td>{{ $row->user->nama ?? '-' }}</td>
                                    <td>{{ optional($row->check_in)->format('H:i') ?? '-' }}</td>
                                    <td>{{ optional($row->check_out)->format('H:i') ?? '-' }}</td>
                                    <td><span class="badge bg-info text-capitalize">{{ $row->status }}</span></td>
                                    <td>{{ $row->keterangan }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit-{{ $row->id }}"><i class="uil uil-edit"></i> Edit</button>
                                    </td>

                                    <div class="modal fade" id="edit-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Edit Absensi - {{ $row->user->nama ?? '-' }} ({{ optional($row->tanggal)->format('d-m-Y') }})</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ url('admin/absensi/rekap/update/'.$row->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select">
                                                                @foreach (['hadir', 'izin', 'sakit', 'alpha', 'cuti'] as $st)
                                                                    <option value="{{ $st }}" {{ $row->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Check In</label>
                                                            <input type="datetime-local" name="check_in" class="form-control" value="{{ optional($row->check_in)->format('Y-m-d\TH:i') }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Check Out</label>
                                                            <input type="datetime-local" name="check_out" class="form-control" value="{{ optional($row->check_out)->format('Y-m-d\TH:i') }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Keterangan</label>
                                                            <textarea name="keterangan" class="form-control" rows="2">{{ $row->keterangan }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                                                        <button type="submit" class="btn btn-success"><i class="uil uil-edit"></i> Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
