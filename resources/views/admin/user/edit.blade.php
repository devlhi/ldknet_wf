@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->

    @foreach ($account as $row)

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


                <div class="row">
                    <div class="col-xl-8">
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
                                <form class="form-horizontal" action="{{ url('admin/manage/user/update/' . $row->id) }}" role="form" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <div class="row mb-3">
                                            <label for="nama" class="col-sm-2 col-form-label">Nama </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="nama" value="{{ $row->nama }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="level" class="col-sm-2 col-form-label">Level </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="level" value="{{ $row->level }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="password" class="col-sm-2 col-form-label">Password Baru </label>
                                            <div class="col-sm-10">
                                                <input type="password" class="form-control" name="password" id="password" placeholder="Masukan password baru" required>
                                            </div>
                                        </div>


                                        <a href="{{ url('admin/manage/user') }}" class="btn btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>

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
