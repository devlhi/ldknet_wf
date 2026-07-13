@extends('auth.layout')

@section('content')
    @php
        $bulanNama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    @endphp

    <div class="mb-4">
        <h4 class="mb-1">Cek Tagihan</h4>
        <p class="text-muted mb-0">Masukkan ID pelanggan untuk melihat tagihan internet Anda.</p>
    </div>

    @if (session('auth_errors'))
        <div class="alert alert-danger" role="alert">
            @foreach (session('auth_errors') as $err)
                <p class="mb-0">{{ $err }}</p>
            @endforeach
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $err)
                <p class="mb-0">{{ $err }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ url('cek/proses') }}" method="post">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="idpel">ID Pelanggan</label>
            <div class="an-field">
                <i class="uil-user an-field-icon" aria-hidden="true"></i>
                <input type="text" class="form-control" id="idpel" name="idpel" placeholder="Contoh: P-0001" value="{{ old('idpel') }}" autocomplete="off" required>
            </div>
        </div>

        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label" for="bulan">Bulan</label>
                <select name="bulan" id="bulan" class="form-control" required>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" @if ($i == (int) old('bulan', date('n'))) selected @endif>{{ $bulanNama[$i] }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label" for="tahun">Tahun</label>
                <select name="tahun" id="tahun" class="form-control" required>
                    @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" @if ($y == (int) old('tahun', date('Y'))) selected @endif>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button class="btn an-btn-gradient" type="submit"><i class="uil-invoice"></i> Cek Tagihan</button>
        </div>
    </form>

    <div class="d-grid mt-3">
        <a href="{{ url('auth/login') }}" class="btn an-btn-outline"><i class="uil-arrow-circle-left"></i> Login Pelanggan</a>
    </div>
@endsection
