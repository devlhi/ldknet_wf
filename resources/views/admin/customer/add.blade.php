@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->

    @foreach ($getservice as $service)
        <div class="page-content">
            <div class="container-fluid">
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                        </div>
                    </div>
                </div>
                <!-- end page title -->
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="text-center mb-5">
                            <h4>Form Pelanggan Baru</h4>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
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
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title mb-4"></h4>
                                <form class="form-horizontal" action="{{ url('admin/customer/add/proses') }}" role="form" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <div class="row mb-3">
                                            <label for="paket" class="col-sm-2 col-form-label">Paket </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="package" id="package" value="{{ $service->paket }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Nama </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="name" placeholder="Masukan Nama">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Nomor Handphone </label>
                                            <div class="col-sm-10">
                                                <input type="number" class="form-control" name="nohp" placeholder="Masukan Nomor Handphone">

                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Email </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="email" placeholder="Masukan Email">

                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Alamat </label>
                                            <div class="col-sm-10">
                                                <textarea type="text" class="form-control" name="alamat" id="alamat"></textarea>

                                            </div>
                                        </div>


                                        <hr>
                                        <div class="row mb-3">
                                            <label for="idpel" class="col-sm-2 col-form-label">ID Pelanggan</label>
                                            <div class="col-sm-10">
                                                @foreach ($idpel as $data)
                                                    @php
                                                        $a = $data->idpel;
                                                        $char = 'P-';
                                                        $urutan = (int) substr($a, 2, 4);
                                                        $urutan++;
                                                        $kode = $char.sprintf('%04s', $urutan);
                                                    @endphp
                                                    <input type="text" class="form-control" name="idpel" value="{{ $kode }}" readonly>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="password" class="col-sm-2 col-form-label">Password</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="password" id="password" value="{{ $password }}">
                                            </div>
                                        </div>

                                        <hr>
                                        <div class="row mb-3">
                                            <label for="router" class="col-sm-2 col-form-label">Router / Server</label>
                                            <div class="col-sm-10">
                                                <select class="form-select" aria-label="Default select example" name="router" id="router" required>
                                                    <option value=""> Silahkan pilih router</option>

                                                    @foreach ($getrouter as $row)
                                                        <option value="{{ $row->id }}">{{ $row->nama }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="pppoe" class="col-sm-2 col-form-label">Username</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="uname_pppoe" id="uname_pppoe" placeholder="Masukan Username PPPOE">

                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="pppoe" class="col-sm-2 col-form-label">Password</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="pass_pppoe" id="pass_pppoe" placeholder="Masukan Password PPPOE">

                                            </div>
                                        </div>



                                        <a href="{{ url('admin/customers') }}" class="btn btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>

                                        <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Submit</button>

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    @endforeach
@endsection

@section('scripts')
    {{-- Catatan: handler #billpriod/#datefix dan #jenip/ipadd legacy dihapus —
         elemen-elemen tersebut tidak ada di form ini (dead JS dari form CI4 lama). --}}
@endsection
