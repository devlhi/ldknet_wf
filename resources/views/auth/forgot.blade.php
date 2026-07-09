@extends('auth.layout')

@section('content')
    <div class="mb-4">
        <h4 class="mb-1">Lupa Password</h4>
        <p class="text-muted mb-0">Masukan email terdaftar untuk reset password.</p>
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

    <form method="post" action="{{ url('auth/sendforgot') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="text" class="form-control" id="email" name="email" placeholder="Masukan email" value="{{ old('email') }}">
        </div>

        <div class="d-grid mt-4">
            <button class="btn an-btn-gradient" type="submit"><i class="uil-location-arrow"></i> Reset Password</button>
        </div>
    </form>

    <div class="mt-3 text-center">
        <a href="{{ url('auth/login') }}" class="text-muted"><i class="uil-arrow-left"></i> Kembali ke Login</a>
    </div>
@endsection
