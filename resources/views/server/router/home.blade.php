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
                        <h4 class="mb-0">Dashboard Router</h4>
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

                    @if (auth()->user()->level === 'developer')
                        <div>
                            <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add Router</button>
                        </div>
                    @endif

                    <div class="card">

                        <div class="card-body">
                            <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="float-left">
                                                <h6 class="modal-title" id="custom-width-modalLabel">Tambah data router</h6>
                                            </div>
                                            <div class="float-right">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" action="{{ url('server/router/add') }}" role="form" method="POST">
                                                @csrf

                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">Nama</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="nama" class="form-control" placeholder="Router A">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">Dns</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="dns" class="form-control" placeholder="mbs.id">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">IP / Host</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="host" class="form-control" placeholder="Masukan IP Address Router">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-12 control-label">Username</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="username" class="form-control" placeholder="Masukan username">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label">Password </label>
                                                    <div class="col-md-12">
                                                        <input type="password" name="password" class="form-control" placeholder="Masukan password">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label">Traffic nterface</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="interface" class="form-control" placeholder="ether1">
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                    <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="fa fa-refresh"></i> Reset</button>
                                                    <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light" name="add"><i class="fa fa-plus"></i> Tambah</button>
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
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($getData as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $row->nama }}</td>

                                            <td>
                                                <a href="{{ url('server/router/connect/' . $row->id) }}" class="btn btn-sm btn-success"><i class='uil uil-link'></i> Connect</a>

                                                @if (auth()->user()->level === 'developer')
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit-{{ $row->id }}"><i class="uil uil-edit"></i> Edit</button>

                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i> Delete</button>
                                                @endif

                                                <!--- Modal Edit -->
                                                <div class="modal fade" id="edit-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <div class="float-left">
                                                                    <h6 class="modal-title" id="custom-width-modalLabel">Edit Data</h6>
                                                                </div>
                                                                <div class="float-right">
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form class="form-horizontal" action="{{ url('server/router/update/' . $row->id) }}" role="form" method="POST">
                                                                    @csrf

                                                                    <div class="form-group">
                                                                        <label class="col-md-2 control-label">Nama</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="nama" class="form-control" value="{{ $row->nama }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="col-md-2 control-label">Dns</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="dns" class="form-control" value="{{ $row->dns }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="col-md-12 control-label">IP / Host</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="host" class="form-control" value="{{ $row->ip }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="col-md-12 control-label">Username </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="username" class="form-control" value="{{ $row->username }}">
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group">
                                                                        <label class="col-md-12 control-label">Password </label>
                                                                        <div class="col-md-12">
                                                                            <input type="password" name="password" class="form-control" placeholder="Masukan password">
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-group">
                                                                        <label class="col-md-12 control-label">Traffic Interface </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" name="interface" class="form-control" value="{{ $row->interface }}">
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

                                                <!--- Modal Delete -->
                                                <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Data</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Apakah anda ingin menghapus Data <b><u>{{ $row->nama }}</u></b> ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <a class="btn btn-primary" href="{{ url('server/router/delete/' . $row->id) }}">Yes</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
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
