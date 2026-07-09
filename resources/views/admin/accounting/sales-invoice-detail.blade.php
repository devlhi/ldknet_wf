@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        @php
            $badge = ['unpaid' => 'bg-warning', 'partial' => 'bg-info', 'paid' => 'bg-success', 'void' => 'bg-secondary', 'draft' => 'bg-light text-dark'];
            $outstanding = (float) $invoice->total - (float) $invoice->paid;
        @endphp

        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-file-alt me-1"></i> Faktur {{ $invoice->number }}
                            <span class="badge {{ $badge[$invoice->status] ?? 'bg-light text-dark' }}">{{ strtoupper($invoice->status) }}</span>
                        </h4>
                        <div class="d-flex gap-2">
                            @if ($outstanding > 0.005)
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="uil uil-money-bill"></i> Terima Pembayaran</button>
                            @endif
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting/sales') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Kepada</h6>
                                <h5>{{ $invoice->contact->name ?? '-' }}</h5>
                                <div class="small text-muted">{{ $invoice->contact->address ?? '' }}</div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th class="text-end">Tanggal</th><td class="text-end">{{ $invoice->date->format('d/m/Y') }}</td></tr>
                                    <tr><th class="text-end">Jatuh Tempo</th><td class="text-end">{{ $invoice->due_date?->format('d/m/Y') ?: '-' }}</td></tr>
                                    <tr><th class="text-end">Referensi</th><td class="text-end">{{ $invoice->reference ?: '-' }}</td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Deskripsi</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Jumlah</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $item)
                                        <tr>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-end">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="3" class="text-end">Subtotal</th><td class="text-end">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Diskon</th><td class="text-end">Rp {{ number_format($invoice->discount, 0, ',', '.') }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Pajak</th><td class="text-end">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td></tr>
                                    <tr class="fw-bold"><th colspan="3" class="text-end">TOTAL</th><td class="text-end">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Dibayar</th><td class="text-end">Rp {{ number_format($invoice->paid, 0, ',', '.') }}</td></tr>
                                    <tr class="fw-bold text-danger"><th colspan="3" class="text-end">Sisa</th><td class="text-end">Rp {{ number_format($outstanding, 0, ',', '.') }}</td></tr>
                                </tfoot>
                            </table>
                        </div>

                        @if ($invoice->notes)
                            <div class="alert alert-light"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('admin/accounting/sales/pay/'.$invoice->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Terima Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Diterima di Akun</label>
                        <select name="account_id" class="form-select" required>
                            @foreach ($cashAccounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nominal (sisa: Rp {{ number_format($outstanding, 0, ',', '.') }})</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ $outstanding }}" max="{{ $outstanding }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
