<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $area }} Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="authentication-bg">
    <div class="container my-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body p-4 text-center">
                        <h4 class="text-primary">{{ $area }} Dashboard</h4>
                        <p class="text-muted">Login berhasil sebagai <strong>{{ auth()->user()->nama }}</strong> ({{ auth()->user()->level }})</p>
                        <p class="text-muted">Modul {{ $area }} belum dimigrasi — halaman ini placeholder.</p>
                        <a href="{{ url('auth/logout') }}" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
