@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-receipt me-1"></i> Tagihan Pembelian</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ url('admin/accounting/purchases/create') }}" class="btn btn-sm btn-primary"><i class="uil uil-plus"></i> Tagihan Baru</a>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Semua Status --</option>
                                    @foreach (['unpaid' => 'Belum Bayar', 'partial' => 'Sebagian', 'paid' => 'Lunas', 'void' => 'Batal'] as $k => $v)
                                        <option value="{{ $k }}" {{ $status === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nomor tagihan..."></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary">Cari</button></div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nomor</th><th>Tanggal</th><th>Pemasok</th><th>Jatuh Tempo</th>
                                        <th class="text-end">Total</th><th class="text-end">Dibayar</th><th class="text-center">Status</th><th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $badge = ['unpaid' => 'bg-warning', 'partial' => 'bg-info', 'paid' => 'bg-success', 'void' => 'bg-secondary', 'draft' => 'bg-light text-dark']; @endphp
                                    @forelse ($bills as $bill)
                                        <tr>
                                            <td><a href="{{ url('admin/accounting/purchases/detail/'.$bill->id) }}">{{ $bill->number }}</a></td>
                                            <td>{{ $bill->date->format('d/m/Y') }}</td>
                                            <td>{{ $bill->contact->name ?? '-' }}</td>
                                            <td>{{ $bill->due_date?->format('d/m/Y') ?: '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($bill->total, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($bill->paid, 0, ',', '.') }}</td>
                                            <td class="text-center"><span class="badge {{ $badge[$bill->status] ?? 'bg-light text-dark' }}">{{ strtoupper($bill->status) }}</span></td>
                                            <td class="text-center">
                                                <a href="{{ url('admin/accounting/purchases/detail/'.$bill->id) }}" class="btn btn-sm btn-outline-info"><i class="uil uil-eye"></i></a>
                                                @if ($bill->paid == 0)
                                                    <a href="{{ url('admin/accounting/purchases/edit/'.$bill->id) }}" class="btn btn-sm btn-outline-primary"><i class="uil uil-edit"></i></a>
                                                    <a href="{{ url('admin/accounting/purchases/delete/'.$bill->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus tagihan ini?')"><i class="uil uil-trash"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted">Belum ada tagihan</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $bills->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
