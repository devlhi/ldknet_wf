@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Paket</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($invoice as $row)
                                        <tr>
                                            <td>{{ $row->code }}</td>
                                            <td>{{ $row->package }}</td>
                                            <td>{{ function_exists('rupiah') ? rupiah($row->price) : $row->price }}</td>
                                            <td>{{ $row->status }}</td>
                                            <td>{{ $row->expdate }}</td>
                                            <td>
                                                <a href="{{ url('user/invoice/detail/'.$row->code) }}" class="btn btn-sm btn-info">Detail</a>
                                                @if ($row->status !== 'Paid')
                                                    <a href="{{ url('user/invoice/payment/'.$row->code) }}" class="btn btn-sm btn-primary">Bayar</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center">Data tidak ditemukan</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
