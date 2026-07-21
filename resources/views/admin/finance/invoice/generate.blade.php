@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                @if (session('auth_errors'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="uil uil-exclamation-triangle me-2"></i>
                        @foreach (session('auth_errors') as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="uil uil-check-circle me-2"></i>
                        @foreach (session('success') as $suc)
                            <div>{{ $suc }}</div>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                    </div>
                @endif

                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h4 class="card-title mb-1"><i class="uil uil-invoice me-1 text-primary"></i> Generate Invoice</h4>
                                <p class="text-muted mb-0">Buat tagihan pelanggan berdasarkan bulan dan tahun yang dipilih.</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">{{ count($customers) }} pelanggan</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/finance/invoice/generate/proses') }}">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="bulan" class="form-label fw-semibold">Bulan Tagihan</label>
                                    <select class="form-select form-select-lg" name="bulan" id="bulan" required>
                                        @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthNumber => $monthName)
                                            <option value="{{ $monthNumber }}" {{ (old('bulan', $currentMonth ?? date('n')) == $monthNumber) ? 'selected' : '' }}>{{ $monthName }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Default mengikuti bulan berjalan.</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="tahun" class="form-label fw-semibold">Tahun Tagihan</label>
                                    <select class="form-select form-select-lg" name="tahun" id="tahun" required>
                                        @foreach (($years ?? range((int) date('Y'), 2020)) as $year)
                                            <option value="{{ $year }}" {{ (old('tahun', $currentYear ?? date('Y')) == $year) ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Tahun dibuat otomatis sampai tahun berjalan.</small>
                                </div>

                                <div class="col-12">
                                    <label for="idpel" class="form-label fw-semibold">Pelanggan</label>
                                    <select class="form-select form-select-lg" name="idpel" id="idpel" required>
                                        <option value="">Pilih ID Pelanggan - Nama Pelanggan</option>
                                        @foreach ($customers as $datacust)
                                            <option value="{{ $datacust->idpel }}" {{ old('idpel') == $datacust->idpel ? 'selected' : '' }}>
                                                {{ $datacust->idpel }} - {{ $datacust->nama }}{{ $datacust->paket ? ' - '.$datacust->paket : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Pastikan pelanggan dipilih sebelum generate agar invoice tidak salah tujuan.</small>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-start gap-2 mt-4 mb-0">
                                <i class="uil uil-info-circle h5 mb-0"></i>
                                <div>
                                    <strong>Catatan aman:</strong> invoice akan dibuat untuk pelanggan dan periode yang dipilih. Hindari generate ganda untuk periode yang sama.
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ url('admin/finance/invoice') }}" class="btn btn-light">Kembali</a>
                                <button class="btn btn-primary px-4" id="buttonadd" type="submit">
                                    <i class="uil uil-invoice me-1"></i> Generate Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.forms.formadd;
        const button = document.getElementById('buttonadd');

        if (form && button) {
            form.addEventListener('submit', function () {
                button.disabled = true;
                button.innerHTML = '<i class="uil uil-spinner-alt spin-icon me-1"></i> Memproses...';
            });
        }
    });
</script>
@endsection
