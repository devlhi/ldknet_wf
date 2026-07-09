@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-book me-1"></i> Jurnal {{ $journal->number }}</h4>
                        <div class="d-flex gap-2">
                            @if ($journal->source === 'manual')
                                <a href="{{ url('admin/accounting/journals/edit/'.$journal->id) }}" class="btn btn-sm btn-primary"><i class="uil uil-edit"></i> Edit</a>
                            @endif
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting/journals') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th style="width:140px;">Nomor</th><td>{{ $journal->number }}</td></tr>
                                    <tr><th>Tanggal</th><td>{{ $journal->date->format('d/m/Y') }}</td></tr>
                                    <tr><th>Sumber</th><td>{{ $journal->source }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th style="width:140px;">Kontak</th><td>{{ $journal->contact?->name ?: '-' }}</td></tr>
                                    <tr><th>Referensi</th><td>{{ $journal->reference ?: '-' }}</td></tr>
                                    <tr><th>Deskripsi</th><td>{{ $journal->description ?: '-' }}</td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Akun</th>
                                        <th>Memo</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($journal->lines as $line)
                                        <tr>
                                            <td><code>{{ $line->account->code ?? '-' }}</code></td>
                                            <td>{{ $line->account->name ?? '-' }}</td>
                                            <td class="small text-muted">{{ $line->memo ?: '-' }}</td>
                                            <td class="text-end">{{ $line->debit > 0 ? 'Rp '.number_format($line->debit, 0, ',', '.') : '' }}</td>
                                            <td class="text-end">{{ $line->credit > 0 ? 'Rp '.number_format($line->credit, 0, ',', '.') : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">TOTAL</td>
                                        <td class="text-end">Rp {{ number_format($journal->lines->sum('debit'), 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($journal->lines->sum('credit'), 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
