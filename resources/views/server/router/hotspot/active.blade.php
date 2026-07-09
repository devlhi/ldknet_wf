@extends('server.router.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->

    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Hotspot Active</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">

                <div class="col-12">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger alert-message" role="alert">
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

                    <div class="card">

                        <div class="card-body">

                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Total Active : {{ $totalhotspotactive }}</th>
                                        <th>Server</th>
                                        <th>User</th>
                                        <th>Mac Address</th>
                                        <th>Uptime</th>
                                        <th>Bytes In</th>
                                        <th>Bytes Out</th>
                                        <th>Time Left</th>
                                        <th>Login By</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hotspotactive as $data)
                                        @php $id = str_replace('*', '', $data['.id']) @endphp
                                        <tr>
                                            <td><button type="button" class="btn btn-sm btn-danger" disabled title="Fitur hapus user hotspot aktif belum tersedia"><i class='uil-trash'></i><strong> Delete</strong></button>
                                            </td>
                                            <td>{{ $data['server'] ?? '' }}</td>
                                            <td>{{ $data['user'] }}</td>
                                            <td>{{ $data['mac-address'] ?? '' }}</td>
                                            <td>{{ formatDTM($data['uptime']) }}</td>
                                            <td>{{ formatBytes($data['bytes-in']) }}</td>
                                            <td>{{ formatBytes($data['bytes-out']) }}</td>
                                            <td>{{ formatDTM($data['session-time-left'] ?? '') }}</td>
                                            <td>{{ $data['login-by'] ?? '' }}</td>
                                            <td>{{ !empty($data['comment']) ? $data['comment'] : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.swal-delete').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: this.dataset.text || 'Data akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f46a6a',
            cancelButtonColor: '#74788d'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = this.href;
            }
        });
    });
});
</script>
@endsection
