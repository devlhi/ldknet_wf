@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Payment Method</h4>
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
                        <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add Payment Method</button>
                    </div>

                    <div class="card">

                        <div class="card-body">
                            <form method="GET" class="mb-3">
                                <button type="submit" name="show_data" value="1" class="btn btn-primary"><i class="uil uil-eye me-1"></i> Tampilkan Data</button>
                            </form>
                            <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="float-left">
                                                <h6 class="modal-title" id="custom-width-modalLabel">Tambah Metode Pembayaran</h6>
                                            </div>
                                            <div class="float-right">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" action="{{ url('admin/gateway/payment/method/add') }}" role="form" method="POST">
                                                @csrf

                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">Nama</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="nama" class="form-control" placeholder="BNI Virtual Account">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">No Rekening</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="norek" class="form-control" placeholder="Wajib jika anda tidak memilih provider">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">Atas Nama</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="atasnama" class="form-control" placeholder="Wajib jika anda tidak memilih provider">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">Note</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="note" class="form-control" placeholder="Wajib jika anda tidak memilih provider">
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">Category </label>
                                                    <div class="col-md-12">
                                                        <select class="form-select" aria-label="Default select example" id="category" name="category">
                                                            <option disabled value="" selected>Pilih Salah satu</option>
                                                            @foreach ($category as $data)
                                                                <option value="{{ $data->category }}">{{ $data->name }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">Provider </label>
                                                    <div class="col-md-12">
                                                        <select class="form-select" aria-label="Default select example" id="gateway" name="gateway">
                                                            <option disabled value="" selected>Pilih Salah satu</option>
                                                            <option value="1">Tanpa Provider</option>

                                                            @foreach ($gateway as $data)
                                                                <option value="{{ $data->name }}">{{ $data->name }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="col-md-12 control-label">Provider Code</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="provider_code" class="form-control" placeholder="Isikan Provider Code ">
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
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
                                        <th>Category</th>
                                        <th>Provider</th>
                                        <th>Provider Code</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($method as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ $row->category }}</td>
                                            <td>{{ $row->provider }}</td>
                                            <td>{{ $row->provider_code }}</td>
                                            @if ($row->status == '1')
                                                <td>Active</td>
                                            @else
                                                <td>Not Active</td>
                                            @endif

                                            <td>
                                                <a href="{{ url('admin/services/edit/'.$row->id) }}" class="btn btn-sm btn-primary"><i class='uil uil-edit'></i> Edit</a>

                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i>Delete</button>
                                                <!--- Modal Delete -->
                                                <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete metode</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Apakah anda ingin menghapus metode <b><u>{{ $row->name }}</u></b> ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <a class="btn btn-primary" href="{{ url('admin/services/delete/'.$row->id) }}">Yes</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if (! $showData)
                                        <tr><td colspan="7" class="text-center text-muted">Klik Tampilkan Data untuk memuat data.</td></tr>
                                    @elseif ($method->isEmpty())
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada metode pembayaran.</td></tr>
                                    @endif
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
