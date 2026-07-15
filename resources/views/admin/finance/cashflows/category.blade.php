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
                        <h4 class="mb-0">Kategori Kas </h4>

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
                        @if (! $showData)
                            <a href="{{ request()->fullUrlWithQuery(['show_data' => 1]) }}" class="btn btn-primary waves-effect waves-light mb-3 me-2"><i class="mdi mdi-database-search me-1"></i> Tampilkan Data</a>
                        @endif
                        <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Kategori Kas</button>
                    </div>
                    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog  modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div class="float-left">
                                        <h6 class="modal-title" id="custom-width-modalLabel">Tambah Kategori Kas</h6>
                                    </div>
                                    <div class="float-right">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                </div>
                                <div class="modal-body">
                                    <form class="form-horizontal" action="{{ url('admin/finance/cash-flows/category/add') }}" role="form" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Nama Kategori Kas</label>
                                            <input type="text" class="form-control" id="formrow-Fullname-input" name="namakat" placeholder="Nama Kategori Kas">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Jenis Kas</label>
                                            <select class="form-select" id="floatingSelectGrid" name="category" aria-label="Floating label select example">
                                                <option selected disabled>Pilih salah satu</option>
                                                <option value="Pemasukan">Pemasukan</option>
                                                <option value="Pengeluaran">Pengeluaran</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Keterangan</label>
                                            <input type="text" class="form-control" id="formrow-Fullname-input" name="keterangan" placeholder="Keterangan">
                                        </div>


                                        <div class="d-flex flex-wrap gap-3 mt-3">
                                            <button type="submit" class="btn btn-primary waves-effect waves-light w-md">Submit</button>
                                            <button type="reset" class="btn btn-outline-danger waves-effect waves-light w-md">Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

                    <div class="card">

                        <div class="card-body">
                            <div class="table-responsive">

                                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No </th>
                                            <th>Nama</th>
                                            <th>Jenis Kas</th>
                                            <th>Keterangan</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $i = 1; @endphp
                                        @foreach ($getdatacategory as $row)
                                            <tr>
                                                <td>{{ $i++ }}</td>
                                                <td>{{ $row->nama }}</td>
                                                <td>{{ $row->jenis }}</td>
                                                <td>{{ $row->keterangan ?? '' }}</td>

                                                @if ($row->keterangan == 'Generate By System')
                                                    <td></td>
                                                @else
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i>Delete</button>

                                                        <!--- Modal Delete -->
                                                        <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Kategori</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Apakah anda ingin menghapus Kategori <b><u>{{ $row->nama }}</u></b> ?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <a class="btn btn-primary" href="{{ url('admin/finance/cash-flows/category/delete/' . $row->id) }}">Yes</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->


        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection
