@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Pengaturan Cron</h4>
                        <div>
                            @if ($cronAlive)
                                <span class="badge bg-success font-size-13">
                                    <i class="uil uil-check-circle me-1"></i> Cron Aktif
                                </span>
                            @else
                                <span class="badge bg-danger font-size-13">
                                    <i class="uil uil-times-circle me-1"></i> Cron Tidak Aktif
                                </span>
                            @endif
                            <small class="text-muted ms-2">
                                @if ($lastHeartbeat)
                                    Heartbeat terakhir: {{ $lastHeartbeat->format('d M Y H:i:s') }}
                                @else
                                    Belum ada heartbeat — pastikan cron job / scheduler sudah dipasang.
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger" role="alert">
                            @foreach (session('auth_errors') as $err)
                                {{ $err }}
                            @endforeach
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                            @foreach (session('success') as $suc)
                                {{ $suc }}
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                        </div>
                    @endif

                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="mb-2"><i class="uil uil-link me-1"></i> URL Cron</h5>
                            <p class="text-muted mb-2">
                                Salin URL ini lalu tempel ke <b>Cron Job</b> di panel hosting Anda (cPanel/Plesk),
                                set jalan <b>tiap 1 menit</b>. Scheduler akan mengeksekusi task sesuai jam yang Anda atur di bawah.
                            </p>
                            <div class="input-group">
                                <input type="text" id="cronUrl" class="form-control" value="{{ $cronUrl }}" readonly
                                    onclick="this.select()">
                                <button class="btn btn-primary" type="button" id="btnCopyCron">
                                    <i class="uil uil-copy me-1"></i> Salin
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Contoh perintah di panel: <code>wget -q -O /dev/null "{{ $cronUrl }}"</code>
                                atau <code>curl -s "{{ $cronUrl }}"</code>
                            </small>

                            <hr class="my-3">

                            <h6 class="mb-2"><i class="uil uil-server-network me-1"></i> Untuk aaPanel</h6>

                            <p class="text-muted mb-1"><b>Cara 1 — Tipe "Access URL":</b> pilih tipe URL, tempel:</p>
                            <div class="input-group input-group-sm mb-3">
                                <input type="text" id="cronAapanelUrl" class="form-control" value="{{ $cronUrl }}" readonly onclick="this.select()">
                                <button class="btn btn-outline-primary" type="button" data-copy-target="cronAapanelUrl">
                                    <i class="uil uil-copy me-1"></i> Salin
                                </button>
                            </div>

                            <p class="text-muted mb-1"><b>Cara 2 — Tipe "Shell Script":</b> tempel perintah ini:</p>
                            <div class="input-group input-group-sm mb-1">
                                <input type="text" id="cronAapanelShell" class="form-control" value="php {{ $cronArtisan }} schedule:run" readonly onclick="this.select()">
                                <button class="btn btn-outline-primary" type="button" data-copy-target="cronAapanelShell">
                                    <i class="uil uil-copy me-1"></i> Salin
                                </button>
                            </div>
                            <small class="text-muted d-block">
                                Set <b>Execution Cycle / Period</b> = <b>N Minutes → 1</b> (tiap 1 menit). Path artisan:
                                <code>{{ $cronArtisan }}</code>
                            </small>
                            <small class="text-muted d-block mt-1">
                                Alternatif via SSH/crontab: <code>* * * * * php {{ $cronArtisan }} schedule:run</code>
                            </small>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">Jadwal Cron</h5>
                            <form action="{{ url('admin/setting/cron/update') }}" method="POST">
                                @csrf
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:40%">Task</th>
                                                <th style="width:20%">Waktu (setiap hari)</th>
                                                <th style="width:25%">Terakhir Jalan</th>
                                                <th style="width:15%" class="text-center">Aktif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($crons as $cron)
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{{ $cron->label }}</div>
                                                        <small class="text-muted">{{ $cron->task }}</small>
                                                    </td>
                                                    <td>
                                                        <input type="time" name="time[{{ $cron->task }}]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $cron->time }}" required>
                                                    </td>
                                                    <td>
                                                        @php $lastRun = $lastRunByTask[$cron->task] ?? null; @endphp
                                                        @if ($lastRun)
                                                            <small>{{ \Illuminate\Support\Carbon::parse($lastRun)->format('d M Y H:i') }}</small>
                                                        @else
                                                            <small class="text-muted">Belum pernah</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input type="checkbox" class="form-check-input"
                                                                name="enabled[{{ $cron->task }}]"
                                                                @checked($cron->enabled)>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @if ($crons->isEmpty())
                                                <tr><td colspan="4" class="text-center text-muted">Belum ada pengaturan cron.</td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="uil uil-save me-1"></i> Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3"><i class="uil uil-history me-1"></i> Riwayat Log Cron (50 terakhir)</h5>
                            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:18%">Task</th>
                                            <th style="width:10%">Status</th>
                                            <th style="width:17%">Mulai</th>
                                            <th style="width:17%">Selesai</th>
                                            <th>Pesan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($cronLogs as $log)
                                            <tr>
                                                <td><small>{{ $log->task }}</small></td>
                                                <td>
                                                    @if ($log->status === 'success')
                                                        <span class="badge bg-success">Sukses</span>
                                                    @elseif ($log->status === 'failed')
                                                        <span class="badge bg-danger">Gagal</span>
                                                    @else
                                                        <span class="badge bg-warning">Berjalan</span>
                                                    @endif
                                                </td>
                                                <td><small>{{ $log->started_at?->format('d M Y H:i:s') }}</small></td>
                                                <td><small>{{ $log->finished_at?->format('d M Y H:i:s') ?? '-' }}</small></td>
                                                <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($log->message ?? '-', 150) }}</small></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    Belum ada log. Log akan terisi setelah task cron pertama berjalan.
                                                </td>
                                            </tr>
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

@section('scripts')
    <script>
        (function () {
            function copyFrom(input, btn) {
                input.select();
                input.setSelectionRange(0, 99999);

                var done = function () {
                    btn.innerHTML = '<i class="uil uil-check me-1"></i> Tersalin';
                    setTimeout(function () {
                        btn.innerHTML = '<i class="uil uil-copy me-1"></i> Salin';
                    }, 2000);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(input.value).then(done);
                } else {
                    document.execCommand('copy');
                    done();
                }
            }

            document.getElementById('btnCopyCron').addEventListener('click', function () {
                copyFrom(document.getElementById('cronUrl'), this);
            });

            document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    copyFrom(document.getElementById(btn.getAttribute('data-copy-target')), btn);
                });
            });
        })();
    </script>
@endsection
