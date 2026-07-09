@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    @foreach ($payment as $row)

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Invoice </h4>

                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                @if (session('auth_errors'))
                                    <div class="alert alert-danger alert-message" role="alert">
                                        @foreach (session('auth_errors') as $err)
                                            {{ $err }}
                                        @endforeach
                                    </div>
                                @endif


                                <form action="{{ url('admin/finance/invoice/update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="target" value="{{ $row->id }}">
                                    <input type="hidden" name="expdate" id="expdate" value="{{ $row->expdate }}">
                                    <input type="hidden" name="code" id="code" value="{{ $row->code }}">

                                    <div class="mb-3">
                                        <label class="form-label">No Invoice</label>
                                        <input type="text" name="invoice" class="form-control" value="{{ $row->code }}" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">ID Pelanggan</label>
                                        <input type="text" name="idpel" class="form-control" value="{{ $row->idpel }}" disabled>
                                        <input type="hidden" name="idpel" value="{{ $row->idpel }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nama</label>
                                        <input type="text" name="user" class="form-control" value="{{ $row->nama }}" disabled>
                                        <input type="hidden" name="user" id="user" value="{{ $row->nama }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Paket</label>
                                        <input type="text" name="package" id="package" class="form-control" value="{{ $row->package }}" disabled>

                                        <input type="hidden" name="package" id="package" value="{{ $row->package }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Jumlah yang harus dibayar</label>
                                        <input type="text" name="price" id="price" class="form-control" value="Rp {{ number_format($row->price) }}" disabled>
                                        <input type="hidden" name="price" id="price" value="{{ $row->price }}">

                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Metode Pembayaran</label>
                                        <div>
                                            <select class="form-select" aria-label="Default select example" name="category">
                                                <option value="CASH">CASH</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tersedia</label>
                                        <div>
                                            <select class="form-select" aria-label="Default select example" name="metode">
                                                <option value="Tunai ( Bayar di kantor )">Tunai ( Bayar di kantor )</option>

                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bukti Pembayaran ( Kwitansi ) </label>
                                        <div>
                                            <input type="file" class="form-control" name="image" id="image"></input>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <select class="form-select" aria-label="Default select example" name="status">
                                                <option value="Paid">Sudah Terbayar</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <div>
                                            <a href="{{ url('admin/finance/invoice') }}" class="btn btn-secondary waves-effect">
                                                Kembali
                                            </a>
                                            <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                                Update
                                            </button>

                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div> <!-- end col -->
                </div>
            </div>
        </div>
    @endforeach
@endsection
