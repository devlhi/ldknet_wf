@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->

    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">User Management</h4>

                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row">
                <div class="col-12">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger alert-message" role="alert">
                            @foreach (session('auth_errors') as $err)
                                {{ $err }}
                            @endforeach
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>

                            @foreach (session('success') as $suc)
                                {{ $suc }}
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                        </div>
                    @endif
                    <div>
                        <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add User </button>
                    </div>
                    <div class="row">
                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">
                            <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="float-left">
                                                <h6 class="modal-title" id="custom-width-modalLabel"><i class="fa fa-plus"></i> Tambah data user</h6>
                                            </div>
                                            <div class="float-right">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
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
                                                    <label class="col-md-2 control-label">Level </label>
                                                    <div class="col-md-12">
                                                        <select class="form-select" aria-label="Default select example" id="level" name="level" required>
                                                            <option disabled value="" selected>Pilih Salah satu</option>
                                                            <option value="admin">Admin</option>
                                                            <option value="finance">Finance </option>
                                                        </select>
                                                    </div>
                                                </div>


                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                    <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="fa fa-refresh"></i> Reset</button>
                                                    <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light" name="add"><i class="fa fa-paper-plane"></i> Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div><!-- /.modal -->
                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Nomor Handphone</th>
                                        <th>Level</th>
                                        <th>Status Akun</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>


                                <tbody>
                                    @foreach ($account as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $row->nama }}</td>
                                            <td>{{ $row->email }}</td>
                                            <td>{{ $row->nomor }}</td>
                                            <td>{{ $row->level }}</td>
                                            <td>{{ $row->status_account }}</td>


                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i>Delete Data</button>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gantipass-{{ $row->id }}"><i class="uil uil-lock"></i>Ganti Password</button>

                                            </td>

                                            <!--- Modal Delete -->
                                            <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete User</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Apakah anda ingin menghapus user <b><u>{{ $row->nama }}</u></b> ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <form action="{{ url('admin/manage/user/delete/'.$row->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-primary">Yes</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!--- Modal Edit -->
                                            <div class="modal fade" id="gantipass-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <div class="float-left">
                                                                <h6 class="modal-title" id="custom-width-modalLabel">Ganti Password</h6>
                                                            </div>
                                                            <div class="float-right">
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form class="form-horizontal" action="{{ url('admin/manage/user/update/'.$row->id) }}" role="form" method="POST">
                                                                @csrf


                                                                <div class="form-group">
                                                                    <label class="col-md-12 control-label">Password </label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="password" class="form-control" placeholder="Masukan password">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                                    <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light" name="add"><i class="uil uil-edit"></i> Edit Data</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div><!-- /.modal-content -->
                                                </div>
                                            </div>


                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->


        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection
