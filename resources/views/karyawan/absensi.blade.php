@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Absensi Hari Ini</h4>
                        <span class="text-muted">{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            </div>

            @if (session('auth_errors'))
                <div class="alert alert-danger" role="alert">
                    @foreach (session('auth_errors') as $err)
                        {{ $err }}
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>
                    @foreach (session('success') as $suc)
                        {{ $suc }}
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">Status Absen</h5>
                            <div class="row">
                                <div class="col-6 border-end">
                                    <p class="text-muted mb-1">Check In</p>
                                    <h4 class="{{ optional($attendance)->check_in ? 'text-success' : 'text-muted' }}">
                                        {{ optional(optional($attendance)->check_in)->format('H:i') ?? '--:--' }}
                                    </h4>
                                </div>
                                <div class="col-6">
                                    <p class="text-muted mb-1">Check Out</p>
                                    <h4 class="{{ optional($attendance)->check_out ? 'text-danger' : 'text-muted' }}">
                                        {{ optional(optional($attendance)->check_out)->format('H:i') ?? '--:--' }}
                                    </h4>
                                </div>
                            </div>
                            @if ($attendance)
                                <div class="mt-3">
                                    <span class="badge bg-info text-capitalize">Status: {{ $attendance->status }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Aksi Absen</h5>

                            @if (! $attendance)
                                <form action="{{ url('karyawan/absensi/check-in') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="lat" id="lat-in">
                                    <input type="hidden" name="lng" id="lng-in">
                                    <div class="mb-3">
                                        <label class="form-label">Status Kehadiran</label>
                                        <select name="status" class="form-select" required>
                                            <option value="hadir">Hadir</option>
                                            <option value="izin">Izin</option>
                                            <option value="sakit">Sakit</option>
                                            <option value="cuti">Cuti</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Foto (opsional)</label>
                                        <input type="file" name="foto" accept="image/*" capture="user" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Keterangan (opsional)</label>
                                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100"><i class="uil uil-sign-in-alt me-1"></i> Simpan Absensi (Check In)</button>
                                </form>
                            @elseif ($attendance->status !== 'hadir')
                                <div class="alert alert-info mb-0 text-center">
                                    <i class="uil uil-info-circle"></i> Absensi hari ini tercatat sebagai <b class="text-capitalize">{{ $attendance->status }}</b>.
                                </div>
                            @elseif (! $attendance->check_out)
                                <form action="{{ url('karyawan/absensi/check-out') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="lat" id="lat-out">
                                    <input type="hidden" name="lng" id="lng-out">
                                    <div class="mb-3">
                                        <label class="form-label">Foto (opsional)</label>
                                        <input type="file" name="foto" accept="image/*" capture="user" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100"><i class="uil uil-sign-out-alt me-1"></i> Check Out</button>
                                </form>
                            @else
                                <div class="alert alert-success mb-0 text-center">
                                    <i class="uil uil-check-circle"></i> Absensi hari ini sudah lengkap. Terima kasih!
                                </div>
                            @endif

                            <a href="{{ url('karyawan/absensi/history') }}" class="btn btn-light w-100 mt-2"><i class="uil uil-history me-1"></i> Riwayat Absensi</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            if (!navigator.geolocation) {
                return;
            }
            navigator.geolocation.getCurrentPosition(function(pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                ['lat-in', 'lat-out'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = lat;
                });
                ['lng-in', 'lng-out'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = lng;
                });
            });
        })();
    </script>
@endsection
