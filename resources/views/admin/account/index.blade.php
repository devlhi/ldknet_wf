@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Pengaturan Akun </h4>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Informasi Akun</h3>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" value="{{ $user->nama }}" disabled>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="{{ $user->email }}" disabled>

                        </div>


                        <div class="mb-3">
                            <label class="form-label">Nomor Handphone</label>
                            <input type="text" class="form-control" value="{{ $user->nomor }}" disabled>

                        </div>



                        <div>
                            <div>


                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Ganti Password</h3>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-message" role="alert">
                                @foreach ($errors->all() as $err)
                                    {{ $err }}
                                @endforeach
                            </div>
                        @endif

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
                        <form action="{{ url($user->level === 'finance' ? 'finance/account/changepassword' : 'admin/account/changepassword') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Password Saat ini</label>
                                <input type="password" name="current_password" class="form-control" placeholder="Masukan Password saat ini">

                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Masukan Password Baru">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Masukan Konfirmasi Password">
                            </div>

                            <div>
                                <div>

                                    <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                        <i class="uil uil-save"></i>
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
@endsection
