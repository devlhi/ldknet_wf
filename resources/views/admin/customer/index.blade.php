@extends('admin.layout')

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
                        <h4 class="mb-0">Customers</h4>

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
                    <div>
                        <a href="{{ url('admin/customer/add') }}" class="btn btn-success waves-effect waves-light mb-3"><i class="mdi mdi-plus me-1"></i> Add customers
                        </a>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="an-stat-label">Pelanggan Aktif</p>
                                            <h4 class="an-stat-value"><span data-plugin="counterup">{{ number_format($customerActive) }}</span></h4>
                                        </div>
                                        <span class="an-stat-icon green"><i class="uil uil-users-alt"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->

                        <div class="col-md-6 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="an-stat-label">Pelanggan Isolir</p>
                                            <h4 class="an-stat-value"><span data-plugin="counterup">{{ number_format($customerIsolir) }}</span></h4>
                                        </div>
                                        <span class="an-stat-icon amber"><i class="uil uil-ban"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->

                        <div class="col-md-6 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="an-stat-label">Pelanggan Berhenti</p>
                                            <h4 class="an-stat-value"><span data-plugin="counterup">{{ number_format($customerEnd) }}</span></h4>
                                        </div>
                                        <span class="an-stat-icon red"><i class="uil uil-user-times"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->


                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">
                            <!-- Toolbar filter -->
                            <div class="row g-2 align-items-end mb-3 an-cust-toolbar">
                                <div class="col-sm-6 col-md-4 col-xl-3">
                                    <label class="form-label mb-1" for="filterServer">Server</label>
                                    <select id="filterServer" class="form-select form-select-sm">
                                        <option value="all">Semua Server</option>
                                        @foreach ($routers as $id => $namaRouter)
                                            <option value="{{ $id }}">{{ $namaRouter }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-4 col-xl-3">
                                    <label class="form-label mb-1" for="filterStatus">Status</label>
                                    <select id="filterStatus" class="form-select form-select-sm">
                                        <option value="all">Semua Status</option>
                                        <option value="Active">Active</option>
                                        <option value="Isolir">Isolir</option>
                                        <option value="Berhenti">Berhenti</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-xl-6 text-md-end">
                                    <button type="button" id="showCustomerData" class="btn btn-sm btn-primary">
                                        <i class="uil uil-eye me-1"></i> Tampilkan Data
                                    </button>
                                    <a href="{{ url('admin/customer/exportexcel') }}" class="btn btn-sm btn-outline-success">
                                        <i class="uil uil-file-export me-1"></i> Export Excel
                                    </a>
                                </div>
                            </div>

                            <div id="customerDataPrompt" class="alert alert-info mb-0">
                                Klik <strong>Tampilkan Data</strong> untuk memuat data pelanggan.
                            </div>
                            <div id="customerTableContainer" class="table-responsive d-none">

                                <table id="datatable-customers" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>ID Pelanggan</th>
                                            <th>Nama Pelanggan</th>
                                            <th>Layanan</th>
                                            <th>Server</th>
                                            <th>Mode</th>
                                            @if (auth()->user()->level === 'developer')
                                                <th>User PPPOE</th>
                                            @endif
                                            <th>Masa Aktif</th>
                                            <th>Status</th>
                                            <th>Alamat</th>
                                            <th>Nama ODP</th>
                                            <th>Port ODP</th>
                                            <th>Aksi</th>
                                            @if (auth()->user()->level === 'developer')
                                                <th>Hapus</th>
                                            @endif

                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->


        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <!--- Shared Modal: Ganti Password -->
    <div class="modal fade" id="modalGantiPass" tabindex="-1" aria-labelledby="modalGantiPassLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="float-left">
                        <h6 class="modal-title" id="modalGantiPassLabel">Ganti Password</h6>
                    </div>
                    <div class="float-right">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" id="formGantiPass" action="" role="form" method="POST">
                        @csrf
                        <p class="text-muted mb-3">Pelanggan: <b><u id="gantiPassNama"></u></b></p>
                        <div class="form-group">
                            <label class="col-md-12 control-label">Password </label>
                            <div class="col-md-12">
                                <input type="text" name="password" class="form-control" placeholder="Masukan password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                            <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light"><i class="uil uil-edit"></i> Edit Data</button>
                        </div>
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div>
    </div>

    <!--- Shared Modal: Hapus Data -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="float-left">
                        <h6 class="modal-title" id="modalHapusLabel">Hapus Data</h6>
                    </div>
                    <div class="float-right">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    Apakah anda ingin menghapus customer <b><u id="hapusNama"></u></b> ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a class="btn btn-primary" id="hapusConfirm" href="#">Yes</a>
                </div>
            </div><!-- /.modal-content -->
        </div>
    </div>

    <style>
        .an-cust-toolbar .form-label {
            font-size: .78rem;
            font-weight: 600;
            color: #64748b;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(function () {
            @php $isDeveloper = auth()->user()->level === 'developer'; @endphp

            // Kolom aksi tidak bisa di-sort. Index menyesuaikan level developer.
            var nonOrderable = @json($isDeveloper ? [12, 13] : [11]);

            var table = null;

            function initializeCustomerTable() {
                if (table) {
                    table.ajax.reload();
                    return;
                }

                $('#customerDataPrompt').addClass('d-none');
                $('#customerTableContainer').removeClass('d-none');
                table = $('#datatable-customers').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    order: [[0, 'asc']],
                    language: {
                        processing: '<span class="an-dt-ring"></span> Memuat...',
                        emptyTable: 'Tidak ada data pelanggan',
                        zeroRecords: 'Data tidak ditemukan',
                        info: 'Menampilkan _START_ - _END_ dari _TOTAL_ pelanggan',
                        infoEmpty: 'Menampilkan 0 pelanggan',
                        infoFiltered: '(disaring dari _MAX_ total)',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        search: 'Cari:',
                        paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                    },
                    ajax: {
                        url: '{{ url('admin/customer/data') }}',
                        data: function (d) {
                            d.show_data = 1;
                            d.router = $('#filterServer').val();
                            d.status = $('#filterStatus').val();
                        }
                    },
                    columnDefs: [
                        { targets: nonOrderable, orderable: false, searchable: false }
                    ]
                });
            }

            $('#showCustomerData').on('click', initializeCustomerTable);

            $('#filterServer, #filterStatus').on('change', function () {
                if (table) {
                    table.ajax.reload();
                }
            });

            // Isi modal share dari data-* tombol yang diklik
            $('#modalGantiPass').on('show.bs.modal', function (e) {
                var btn = $(e.relatedTarget);
                $('#formGantiPass').attr('action', btn.data('action'));
                $('#gantiPassNama').text(btn.data('nama'));
                $('#formGantiPass input[name="password"]').val('');
            });

            $('#modalHapus').on('show.bs.modal', function (e) {
                var btn = $(e.relatedTarget);
                $('#hapusConfirm').attr('href', btn.data('action'));
                $('#hapusNama').text(btn.data('nama'));
            });

            $('.dataTables_length select').addClass('form-select form-select-sm');
        });
    </script>
@endsection
