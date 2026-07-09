@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    @foreach ($getData as $row)

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Edit Data OLT </h4>
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

                                <form class="form-horizontal" action="{{ url('server/olt/update') }}" role="form" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Nama Server </label>
                                        <input type="text" name="nama" class="form-control" value="{{ $row->nama }}">
                                        <input type="hidden" name="target" class="form-control" value="{{ $row->id }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">IP / Host</label>
                                        <input type="text" name="host" class="form-control" value="{{ $row->ip }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" value="{{ $row->username }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="text" name="password" class="form-control" value="{{ legacy_decrypt($row->password) }}">
                                    </div>

                                    <div>
                                        <div>
                                            <a href="{{ url('server/olt') }}" class="btn btn-success waves-effect waves-light me-1 "> Kembali</a>

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
