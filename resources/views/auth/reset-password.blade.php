@extends('auth.layout')

@section('content')
    <div class="mb-4">
        <h4 class="mb-1">Reset Password</h4>
        <p class="text-muted mb-0">Buat password baru untuk akun Anda.</p>
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

    <form method="post" action="{{ url('auth/reset-password/'.$token.'/update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukan Password">
        </div>

        <div class="mb-3">
            <label class="form-label" for="confirm_password">Konfirmasi Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Masukan Konfirmasi Password">
        </div>

        <div class="d-grid mt-4">
            <button class="btn an-btn-gradient" type="submit">Update Password</button>
        </div>
    </form>
@endsection
