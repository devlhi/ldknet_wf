<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h4>{{ $title }}</h4>
                        @foreach ($content as $invoice)
                            <ul class="list-group mb-4">
                                <li class="list-group-item"><strong>Kode:</strong> {{ $invoice->code }}</li>
                                <li class="list-group-item"><strong>ID Pelanggan:</strong> {{ $invoice->idpel }}</li>
                                <li class="list-group-item"><strong>Nama:</strong> {{ $invoice->nama }}</li>
                                <li class="list-group-item"><strong>Paket:</strong> {{ $invoice->package }}</li>
                                <li class="list-group-item"><strong>Harga:</strong> {{ function_exists('rupiah') ? rupiah($invoice->price) : $invoice->price }}</li>
                                <li class="list-group-item"><strong>Status:</strong> {{ $invoice->status === 'Error' ? 'Cancel' : $invoice->status }}</li>
                                <li class="list-group-item"><strong>Jatuh Tempo:</strong> {{ $invoice->expdate }}</li>
                            </ul>
                        @endforeach
                        <a href="{{ url('cek') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
