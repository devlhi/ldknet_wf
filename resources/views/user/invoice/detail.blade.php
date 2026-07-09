@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
                        <p class="text-muted">Dicetak: {{ $date }}</p>
                        @foreach ($history as $row)
                            <ul class="list-group mb-3">
                                <li class="list-group-item"><strong>Kode:</strong> {{ $row->code }}</li>
                                <li class="list-group-item"><strong>ID Pelanggan:</strong> {{ $row->idpel }}</li>
                                <li class="list-group-item"><strong>Nama:</strong> {{ $row->nama }}</li>
                                <li class="list-group-item"><strong>Paket:</strong> {{ $row->package }}</li>
                                <li class="list-group-item"><strong>Harga:</strong> {{ function_exists('rupiah') ? rupiah($row->price) : $row->price }}</li>
                                <li class="list-group-item"><strong>Status:</strong> {{ $row->status }}</li>
                                <li class="list-group-item"><strong>Jatuh Tempo:</strong> {{ $row->expdate }}</li>
                            </ul>
                        @endforeach
                        <a href="{{ url('user/invoice') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
