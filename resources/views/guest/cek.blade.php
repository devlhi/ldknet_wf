<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="authentication-bg">
    <div class="container my-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="{{ asset('assets/logo/'.$logo) }}" alt="logo" height="50">
                            <h4 class="mt-3">{{ $titletext }}</h4>
                            <p class="text-muted">Cek tagihan internet Anda</p>
                        </div>
                        @if (session('auth_errors'))
                            <div class="alert alert-danger">
                                @foreach (session('auth_errors') as $err)
                                    <p class="mb-0">{{ $err }}</p>
                                @endforeach
                            </div>
                        @endif
                        <form action="{{ url('cek/proses') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">ID Pelanggan</label>
                                <input type="text" name="idpel" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Bulan</label>
                                    <select name="bulan" class="form-control" required>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" @if($i == date('n')) selected @endif>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Tahun</label>
                                    <select name="tahun" class="form-control" required>
                                        @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Cek Tagihan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
