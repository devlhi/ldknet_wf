@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-book me-1"></i> Jurnal Umum</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ url('admin/accounting/journals/create') }}" class="btn btn-sm btn-primary"><i class="uil uil-plus"></i> Jurnal Baru</a>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-2">
                                <input type="date" name="start" value="{{ $start }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="end" value="{{ $end }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nomor / deskripsi...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-outline-primary">Filter</button>
                                <a href="{{ url('admin/accounting/journals') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nomor</th>
                                        <th>Sumber</th>
                                        <th>Referensi</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($journals as $j)
                                        <tr>
                                            <td>{{ $j->date->format('d/m/Y') }}</td>
                                            <td><a href="{{ url('admin/accounting/journals/detail/'.$j->id) }}">{{ $j->number }}</a></td>
                                            <td><span class="badge bg-light text-dark">{{ $j->source }}</span></td>
                                            <td class="small">{{ $j->reference ?: '-' }}</td>
                                            <td>{{ $j->description ?: '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($j->total, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <a href="{{ url('admin/accounting/journals/detail/'.$j->id) }}" class="btn btn-sm btn-outline-info"><i class="uil uil-eye"></i></a>
                                                @if ($j->source === 'manual')
                                                    <a href="{{ url('admin/accounting/journals/edit/'.$j->id) }}" class="btn btn-sm btn-outline-primary"><i class="uil uil-edit"></i></a>
                                                    <a href="{{ url('admin/accounting/journals/delete/'.$j->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus jurnal ini?')"><i class="uil uil-trash"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada jurnal</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $journals->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
