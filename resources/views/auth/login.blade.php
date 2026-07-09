@extends('auth.layout')

@section('content')
    <div class="mb-4">
        <h4 class="mb-1">Selamat Datang</h4>
        <p class="text-muted mb-0">Silakan masuk untuk melanjutkan.</p>
    </div>

    @if (session('auth_errors'))
        <div class="alert alert-danger" role="alert">
            @foreach (session('auth_errors') as $err)
                {{ $err }}
            @endforeach
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $err)
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

    <form method="post" action="{{ url('auth/auth') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email / Nomor HP</label>
            <div class="an-field">
                <i class="uil-envelope an-field-icon" aria-hidden="true"></i>
                <input type="text" class="form-control" id="email" name="email" placeholder="Masukan email atau nomor handphone" value="{{ old('email') }}" autocomplete="username">
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <label class="form-label" for="password">Password</label>
                <a href="{{ url('auth/forgot') }}" class="an-forgot">Lupa password?</a>
            </div>
            <div class="an-field">
                <i class="uil-lock an-field-icon" aria-hidden="true"></i>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukan Password" autocomplete="current-password">
                <button class="an-toggle" type="button" id="showPasswordToggle" aria-label="Tampilkan password">
                    <i class="uil-eye"></i>
                </button>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button class="btn an-btn-gradient" type="submit"><i class="uil-arrow-circle-right"></i> Login</button>
        </div>
    </form>

    <div class="d-grid mt-3">
        <a href="{{ url('cek') }}" class="btn an-btn-outline"><i class="uil-invoice"></i> Cek Tagihan</a>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#showPasswordToggle').click(function() {
                var passwordField = $('#password');
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).html('<i class="uil-eye-slash"></i>');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).html('<i class="uil-eye"></i>');
                }
            });
        });
    </script>
@endsection
