@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    @foreach ($content as $row)

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Pengaturan Company </h4>

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

                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>

                                        @foreach (session('success') as $suc)
                                            {{ $suc }}
                                        @endforeach
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                                    </div>
                                @endif

                                <form action="{{ url('admin/setting/company/update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Email </label>
                                        <input type="text" name="email" class="form-control" value="{{ $row->email }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nomor Handphone</label>
                                        <input type="text" name="phone" class="form-control" value="{{ $row->phone_number }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Alamat</label>
                                        <input type="text" name="address" class="form-control" value="{{ $row->address }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Kota</label>
                                        <input type="text" name="city" class="form-control" value="{{ $row->city }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Provinsi</label>
                                        <input type="text" name="province" class="form-control" value="{{ $row->province }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Negara</label>
                                        <input type="text" name="country" class="form-control" value="{{ $row->country }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Kode Pos</label>
                                        <input type="text" name="postal_code" class="form-control" value="{{ $row->postal_code }}">
                                    </div>


                                    <div>
                                        <div>

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
