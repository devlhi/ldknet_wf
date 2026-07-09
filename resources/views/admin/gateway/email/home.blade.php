@extends('admin.layout')

@section('content')
    @foreach ($content as $row)
        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Setting Mail Gateway </h4>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Brevo</h3>
                            </div>
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

                                <form action="{{ url('admin/setting/smtp/update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="target" value="{{ $row->id }}">

                                    <div class="mb-3">
                                        <label class="form-label">API Key Brevo</label>
                                        <input type="text" name="key" class="form-control" value="{{ $row->key }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nama</label>
                                        <input type="text" name="namasmtp" class="form-control" value="{{ $row->nama }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="text" name="emailsmtp" class="form-control" value="{{ $row->email }}">
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

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Informasi</h3>
                            </div>
                            <div class="card-body">
                                <pre>Mohon ikuti arahan admin</pre>
                            </div>
                        </div>
                    </div> <!-- end col -->
                </div>
            </div>
        </div>
    @endforeach
@endsection
