@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

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
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Broadcast Information </h4>
                            <p class="card-title-desc">Fitur ini berguna untuk mengirimkan informasi kepada semua pelanggan via email</p>

                            <form method="post" action="{{ url('admin/cms/broadcast/email/send') }}">
                                @csrf
                                <div class="col-12">
                                    <input type="text" class="form-control" name="subject" placeholder="Masukan Subject">
                                </div>
                                <br>
                                <textarea id="elm1" name="message"></textarea>
                                <br />
                                <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="uil uil-megaphone me-2"></i> Send Broadcast
                                </button>
                            </form>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
@endsection
