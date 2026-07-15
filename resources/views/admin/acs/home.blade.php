@extends('admin.layout')

@section('css')
<style>
    .acs-modal .modal-dialog {
        max-width: 480px;
    }

    .acs-modal .modal-content {
        background: #fff !important;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }

    .acs-modal .modal-header {
        background: #fff;
        border-bottom: 1px solid #eef2f7;
        color: #0f172a;
        padding: 16px 20px;
    }

    .acs-modal .modal-title {
        align-items: center;
        color: #0f172a;
        display: flex;
        font-size: 16px;
        font-weight: 700;
        gap: 8px;
    }

    .acs-modal .modal-title .mdi {
        align-items: center;
        background: #eff6ff;
        border-radius: 8px;
        color: #2563eb;
        display: inline-flex;
        height: 24px;
        justify-content: center;
        width: 24px;
    }

    .acs-modal .btn-close {
        opacity: .7;
    }

    .acs-modal .modal-body {
        background: #fff;
        padding: 20px;
    }

    .acs-modal .form-label {
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .acs-modal .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .acs-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #eef2f7;
        padding: 14px 20px;
    }
</style>
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Managemen ACS</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                @if (session('auth_errors'))
                    <div class="alert alert-danger alert-message" role="alert">
                        @foreach ((array) session('auth_errors') as $err)
                            {{ $err }}
                        @endforeach
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                        @foreach ((array) session('success') as $suc)
                            {{ $suc }}
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                    </div>
                @endif

                @if (auth()->user()->level === 'developer')
                    <div>
                        <button type="button" class="btn btn-primary waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add ACS</button>
                    </div>

                    <div id="myModal" class="modal fade acs-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ url('server/acs/add') }}" role="form" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h6 class="modal-title"><i class="mdi mdi-server-network"></i> Tambah Data ACS</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nama</label>
                                            <input type="text" name="nama" class="form-control" placeholder="ACS Lokal" required>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">IP / Host</label>
                                            <input type="text" name="host" class="form-control" placeholder="Masukan IP Address GenieACS" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                                        <button type="reset" class="btn btn-outline-danger"><i class="mdi mdi-refresh me-1"></i> Reset</button>
                                        <button type="submit" class="btn btn-success"><i class="mdi mdi-plus me-1"></i> Tambah</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        @if (! ($dataLoaded ?? true))
                            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span><i class="mdi mdi-information-outline me-2"></i> Data ACS belum dimuat dari database. Klik tombol untuk memuat data.</span>
                                <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-download"></i> Tampilkan Data
                                </a>
                            </div>
                        @endif
                        <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php ($i = 1)
                                @foreach ($getData as $row)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>{{ $row->nama }}</td>
                                        <td>
                                            <a href="{{ url('server/acs/connect/' . $row->id) }}" class="btn btn-sm btn-success"><i class='uil uil-link'></i> Connect</a>

                                            @if (auth()->user()->level === 'developer')
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit-{{ $row->id }}"><i class="uil uil-edit"></i> Edit</button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i> Delete</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->level === 'developer')
        @foreach ($getData as $row)
            <div class="modal fade acs-modal" id="edit-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ url('server/acs/update/' . $row->id) }}" role="form" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h6 class="modal-title"><i class="mdi mdi-pencil"></i> Edit Data</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama</label>
                                    <input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">IP / Host</label>
                                    <input type="text" name="host" class="form-control" value="{{ $row->url }}" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                                <button type="submit" class="btn btn-success"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade acs-modal" id="delete-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title"><i class="mdi mdi-trash-can-outline"></i> Delete Data</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Apakah anda ingin menghapus Data <b><u>{{ $row->nama }}</u></b> ?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <form action="{{ url('server/acs/delete/' . $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger"><i class="mdi mdi-trash-can-outline me-1"></i> Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
