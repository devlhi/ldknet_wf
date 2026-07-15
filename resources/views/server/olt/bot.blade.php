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
                        <h4 class="mb-0">Bot Whatsapp</h4>
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
                        <a href="{{ url('server/olt') }}" class="btn btn-success waves-effect waves-light mb-3"><i class="uil-arrow-left me-1"></i> Kembali</a>
                        @if (auth()->user()->level === 'developer')
                            <button type="button" class="btn btn-primary waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add Command</button>
                        @endif
                    </div>

                    <div class="card">

                        <div class="card-body">
                            <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="float-left">
                                                <h6 class="modal-title" id="custom-width-modalLabel">Tambah data</h6>
                                            </div>
                                            <div class="float-right">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" action="{{ url('server/olt/bot/whatsapp/add') }}" role="form" method="POST">
                                                @csrf

                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">Command</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="command" class="form-control" placeholder="Masukan Command">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-12 control-label">Keterangan </label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="keterangan" class="form-control" placeholder="Masukan username">
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
                            @if (! ($dataLoaded ?? true))
                                <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span><i class="mdi mdi-information-outline me-2"></i> Data command bot belum dimuat dari database. Klik tombol untuk memuat data.</span>
                                    <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-download"></i> Tampilkan Data
                                    </a>
                                </div>
                            @endif
                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Command</th>
                                        <th>Keterangan</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($getData as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $row->command }}</td>
                                            <td>{{ $row->keterangan }}</td>

                                            <td>
                                                @if (auth()->user()->level === 'developer')
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i>Delete</button>
                                                @endif

                                                <!--- Modal Delete -->
                                                <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Data</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Apakah anda ingin menghapus Data <b><u>{{ $row->command }}</u></b> ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <form action="{{ url('server/olt/bot/whatsapp/delete/' . $row->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-primary">Yes</button>
                                                                </form>
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
