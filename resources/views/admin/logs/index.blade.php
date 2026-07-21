@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Laravel Logs</h4>
                        <a href="{{ url('admin/logs'.($selected ? '?file='.urlencode($selected) : '')) }}" class="btn btn-outline-primary btn-sm">
                            <i class="uil uil-refresh me-1"></i> Muat Ulang
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger" role="alert">
                            @foreach (session('auth_errors') as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            @foreach (session('success') as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <i class="uil uil-shield-exclamation me-1"></i>
                        Log dapat memuat data teknis atau pelanggan. Jangan membagikannya ke pihak yang tidak berwenang.
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @if ($files === [])
                                <div class="text-center text-muted py-5">
                                    <i class="uil uil-file-search-alt font-size-24 d-block mb-2"></i>
                                    Belum ada file log Laravel yang tersedia.
                                </div>
                            @else
                                <div class="row g-3 align-items-end mb-3">
                                    <div class="col-lg-6">
                                        <form action="{{ url('admin/logs') }}" method="GET">
                                            <label for="logFile" class="form-label">File Log</label>
                                            <div class="input-group">
                                                <select name="file" id="logFile" class="form-select" onchange="this.form.submit()">
                                                    @foreach ($files as $file)
                                                        <option value="{{ $file['name'] }}" @selected($selected === $file['name'])>
                                                            {{ $file['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-lg-6 text-lg-end">
                                        @php $selectedFile = $selected ? ($files[$selected] ?? null) : null; @endphp
                                        @if ($selectedFile)
                                            <div class="text-muted small mb-2">
                                                Ukuran {{ number_format($selectedFile['size'] / 1024, 1, ',', '.') }} KB
                                                &middot; diperbarui {{ $selectedFile['modified_at'] }}
                                            </div>
                                        @endif
                                        <button type="button" id="copyLog" class="btn btn-primary">
                                            <i class="uil uil-copy me-1"></i> Salin Log
                                        </button>
                                        <form id="clearLogForm" action="{{ url('admin/logs/clear') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="file" value="{{ $selected }}">
                                            <button type="submit" id="clearLog" class="btn btn-danger">
                                                <i class="uil uil-trash-alt me-1"></i> Bersihkan Log
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        Menampilkan bagian terbaru, maksimal {{ number_format($maxBytes / 1024) }} KB.
                                    </small>
                                    <small class="text-muted">Baris terbaru berada di bagian bawah.</small>
                                </div>

                                <textarea id="logContents" class="form-control font-monospace" rows="28" readonly spellcheck="false" style="white-space:pre;overflow:auto;resize:vertical;">{{ $contents }}</textarea>
                            @endif
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
            var textarea = document.getElementById('logContents');
            var copyButton = document.getElementById('copyLog');
            var clearForm = document.getElementById('clearLogForm');

            if (textarea) {
                textarea.scrollTop = textarea.scrollHeight;
            }

            if (copyButton && textarea) {
                copyButton.addEventListener('click', function () {
                    var button = this;
                    var copied = function () {
                        button.innerHTML = '<i class="uil uil-check me-1"></i> Tersalin';
                        setTimeout(function () {
                            button.innerHTML = '<i class="uil uil-copy me-1"></i> Salin Log';
                        }, 2000);
                    };

                    var fallbackCopy = function () {
                        textarea.select();
                        if (document.execCommand('copy')) {
                            copied();
                        }
                    };

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(textarea.value).then(copied).catch(fallbackCopy);
                        return;
                    }

                    fallbackCopy();
                });
            }

            if (clearForm) {
                clearForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    var form = this;

                    Swal.fire({
                        title: 'Bersihkan log?',
                        text: 'Seluruh isi file log yang dipilih akan dikosongkan dan tidak dapat dikembalikan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Ya, Bersihkan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            }
        })();
    </script>
@endsection
