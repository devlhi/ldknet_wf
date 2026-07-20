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



                                <div class="mb-3">
                                    <label class="form-label">No Invoice</label>
                                    <input type="text" class="form-control" value="{{ $row->code }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ID Pelanggan</label>
                                    <input type="text" class="form-control" value="{{ $row->idpel }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama</label>
                                    <input type="text" class="form-control" value="{{ $row->nama }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Paket</label>
                                    <input type="text" class="form-control" value="{{ $row->package }}" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Jumlah yang diterima</label>
                                    <input type="text" class="form-control" value="Rp {{ number_format($row->received) }}" disabled>

                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Metode Pembayaran</label>
                                    <input type="text" class="form-control" value="{{ $row->category }}" disabled>

                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tersedia</label>
                                    <input type="text" class="form-control" value="{{ $row->service }}" disabled>

                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    @php
                                        if ($row->status == 'Paid') {
                                            $status = 'Sudah terbayar';
                                        } elseif ($row->status == 'Unpaid') {
                                            $status = 'Belum terbayar';
                                        } elseif ($row->status == 'Error') {
                                            $status = 'Cancel';
                                        } else {
                                            $status = $row->status ?? '-';
                                        }
                                    @endphp
                                    <input type="text" class="form-control" value="{{ $status }}" disabled>


                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Di Update Oleh</label>
                                    <input type="text" class="form-control" value="{{ $row->update_by }} " disabled>

                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pada tanggal</label>
                                    <input type="text" class="form-control" value=" {{ tanggal($row->last_update) }}" disabled>

                                </div>

                                <div>
                                    <div>
                                        <a href="{{ url('admin/finance/invoice') }}" class="btn btn-secondary waves-effect">
                                            Kembali
                                        </a>

                                    </div>

                                </div>
                            </div>
                        </div> <!-- end col -->
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
