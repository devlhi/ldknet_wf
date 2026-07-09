@extends('admin.layout')

@section('content')
    @foreach ($content as $row)
        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Send Email Test </h4>
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
                                <form action="{{ url('admin/setting/smtp/send') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Pengirim</label>
                                        <input type="text" name="sender" class="form-control" value="{{ $row->email }}" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Penerima</label>
                                        <input type="text" name="penerima" class="form-control" placeholder="Masukan Email Penerima">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <input type="text" name="subject" class="form-control" placeholder="Masukan Subject">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Pesan</label>
                                        <input type="text" name="pesan" class="form-control" placeholder="Masukan Pesan">
                                    </div>

                                    <div>
                                        <div>
                                            <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                                <i class="uil uil-envelope"></i>
                                                Kirim
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
