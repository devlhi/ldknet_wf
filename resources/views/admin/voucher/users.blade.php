@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">User Management</h4>
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

                <div>
                    <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add Reseller</button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title"><i class="fa fa-plus"></i> Tambah data user</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form class="form-horizontal" action="{{ url('admin/manage/user/add') }}" role="form" method="POST">
                                            @csrf
                                            <div class="form-group">
                                                <label class="col-md-2 control-label"> Nama</label>
                                                <div class="col-md-12">
                                                    <input type="text" name="nama" class="form-control" placeholder="Masukan Nama">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Email</label>
                                                <div class="col-md-12">
                                                    <input type="text" name="email" class="form-control" placeholder="Masukan Email">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Nomor Whatsapp</label>
                                                <div class="col-md-12">
                                                    <input type="number" name="nomor" class="form-control" placeholder="Masukan Nomor Whatsapp">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Password</label>
                                                <div class="col-md-12">
                                                    <input type="text" name="password" class="form-control" value="{{ $password }}">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-2 control-label">Level</label>
                                                <div class="col-md-12">
                                                    <select class="form-select" name="level" required>
                                                        <option disabled value="" selected>Pilih Salah satu</option>
                                                        <option value="admin">Admin</option>
                                                        <option value="cs">Customer Service</option>
                                                        <option value="finance">Finance</option>
                                                        <option value="technician">Technician</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                <button type="reset" class="btn btn-danger waves-effect"><i class="fa fa-refresh"></i> Reset</button>
                                                <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light"><i class="fa fa-paper-plane"></i> Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Nomor Handphone</th>
                                    <th>Level</th>
                                    <th>Saldo</th>
                                    <th>Status Akun</th>
                                    <th>Lihat Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php ($i = 1)
                                @foreach ($account as $row)
                                    <tr>
                                        <td>{{ $i++ }}</td>
                                        <td>{{ $row->nama }}</td>
                                        <td>{{ $row->email }}</td>
                                        <td>{{ $row->nomor }}</td>
                                        <td>{{ $row->level }}</td>
                                        <td>{{ $row->balance }}</td>
                                        <td>{{ $row->status_account }}</td>
                                        <td><a href="{{ url('admin/manage/user/edit/' . $row->id) }}" class="btn btn-sm btn-primary"><i class="uil-edit"></i> Edit Data</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
