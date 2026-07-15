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
                        <h4 class="mb-0">Invoice</h4>

                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Filter Data</h5>
                        </div>

                        <div class="card-body">

                            <div class="example-content">
                                <form class="row g-3">

                                    <div class="col-md-6">
                                        <label for="inputbulan" class="form-label">Bulan</label>
                                        <select class="form-select" aria-label="Default select example" name="bulan" id="bulan">
                                            <option value="1">Januari</option>
                                            <option value="2">Februari</option>
                                            <option value="3">Maret</option>
                                            <option value="4">April</option>
                                            <option value="5">Mei</option>
                                            <option value="6">Juni</option>
                                            <option value="7">Juli</option>
                                            <option value="8">Agustus</option>
                                            <option value="9">September</option>
                                            <option value="10">Oktober</option>
                                            <option value="11">November</option>
                                            <option value="12">Desember</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="inputtahun" class="form-label">Tahun</label>
                                        <select class="form-select" aria-label="Default select example" name="tahun" id="tahun">
                                            @foreach ($tahun as $row)
                                                <option value="{{ $row->tahun }}">{{ $row->tahun }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </form>
                                <button type="button" class="btn btn-primary mt-3" id="showDataButton">
                                    <i class="uil uil-eye me-1"></i> Tampilkan Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <!-- end row-->

                    <div class="row">
                        <!-- Tampilkan statistik berdasarkan filter bulan dan tahun -->
                        <!-- Tambahkan ID pada elemen yang ingin diperbarui -->
                        <div class="col-md-6 col-xl-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="float-end mt-2">
                                        <div id="total-revenue-chart"></div>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 mt-1"><span id="invoice-paid" data-plugin="counterup">{{ number_format($getInvoicePaid) }}</span></h4>
                                        <p class="text-muted mb-0">Invoice Terbayar</p>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->

                        <!-- Tambahkan ID pada elemen yang ingin diperbarui -->
                        <div class="col-md-6 col-xl-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="float-end mt-2">
                                        <div id="orders-chart"></div>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 mt-1"><span id="invoice-unpaid" data-plugin="counterup">{{ number_format($getInvoiceUnpaid) }}</span></h4>
                                        <p class="text-muted mb-0">Invoice Belum Terbayar</p>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->



                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">
                            {{-- ID unik (bukan #datatable) supaya tidak di-auto-init ganda
                                 oleh assets/js/pages/datatables.init.js -> "Cannot reinitialise". --}}
                            <table id="datatable-invoices" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>No </th>
                                        <th>ID Pelanggan</th>
                                        <th>No Invoice</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Invoice</th>
                                        <th>Paket</th>
                                        <th>Jumlah Tagihan</th>
                                        <th>Status</th>
                                        <th>Periode </th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>


                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->


        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection

@section('scripts')
    <script>
        var base_url = '{{ url('/') }}/';
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        $(function () {
            $('#bulan').val(String(new Date().getMonth() + 1));
            var table = null;
            var activated = false;

            function initializeTable() {
                if (table) return table;

                table = $('#datatable-invoices').DataTable({
                    processing: true,
                    language: {
                        processing: '<span class="an-dt-ring"></span> Memuat...',
                        emptyTable: 'Tidak ada data invoice',
                        zeroRecords: 'Data tidak ditemukan',
                        info: 'Menampilkan _START_ - _END_ dari _TOTAL_ invoice',
                        infoEmpty: 'Menampilkan 0 invoice',
                        infoFiltered: '(disaring dari _MAX_ total)',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        search: 'Cari:',
                        paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                    },
                    columns: [
                        { data: null, render: function(data, type, row, meta) { return meta.row + 1; } },
                        { data: 'idpel' },
                        { data: 'code' },
                        { data: 'nama' },
                        {
                            data: 'code', orderable: false,
                            render: function(data, type, row) {
                                var cls = row.status === 'Paid' ? 'btn-success' : 'btn-danger';
                                return '<a href="' + base_url + 'admin/finance/invoice/print/' + row.code + '" target="_blank" class="btn btn-sm ' + cls + '"><i class="uil uil-print"></i> Lihat</a>';
                            }
                        },
                        { data: 'package' },
                        { data: 'price', render: function(data) { return parseFloat(data).toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }); } },
                        {
                            data: 'status',
                            render: function(data, type, row) {
                                var text = row.status;
                                var cls = 'bg-secondary';
                                if (row.status === 'Paid') { text = 'Sudah Terbayar'; cls = 'bg-success'; }
                                else if (row.status === 'Unpaid') { text = 'Belum Terbayar'; cls = 'bg-danger'; }
                                return '<span class="badge ' + cls + '">' + text + '</span>';
                            }
                        },
                        { data: 'date', render: function(data) { return new Date(data).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }); } },
                        {
                            data: 'status', orderable: false,
                            render: function(data, type, row) {
                                if (row.status !== 'Unpaid') return '';
                                return '<div class="d-flex gap-1 flex-wrap"><a href="' + base_url + 'admin/finance/invoice/edit/' + row.code + '" class="btn btn-sm btn-warning"><i class="uil uil-check-circle"></i> <strong>Konfirmasi Pembayaran</strong></a><a href="' + base_url + 'admin/finance/invoice/bayar/' + row.code + '" class="btn btn-sm btn-primary"><i class="uil uil-credit-card"></i> <strong>Bayar Online</strong></a></div>';
                            }
                        }
                    ]
                });
                return table;
            }

            function loadData() {
                var dataTable = initializeTable();
                var button = $('#showDataButton');
                var bulan = $('#bulan').val();
                var tahun = $('#tahun').val();
                button.prop('disabled', true).html('<i class="uil uil-spinner-alt spin-icon me-1"></i> Memuat...');

                $.when(
                    $.ajax({ url: '{{ url('finance/invoice/filter/getdata') }}', type: 'POST', dataType: 'json', data: { show_data: 1, bulan: bulan, tahun: tahun } }),
                    $.ajax({ url: '{{ url('finance/invoice/filter/ambil-data') }}/' + bulan + '/' + tahun, type: 'GET', dataType: 'json', data: { show_data: 1 } })
                ).done(function(rowsResponse, statsResponse) {
                    dataTable.clear().rows.add(rowsResponse[0]).draw();
                    $('#invoice-paid').text(statsResponse[0].getInvoicePaid || 0);
                    $('#invoice-unpaid').text(statsResponse[0].getInvoiceUnpaid || 0);
                    activated = true;
                }).fail(function(xhr, status, error) {
                    console.error(error);
                }).always(function() {
                    button.prop('disabled', false).html('<i class="uil uil-eye me-1"></i> Tampilkan Data');
                });
            }

            $('#showDataButton').on('click', loadData);
            $('#bulan, #tahun').on('change', function() { if (activated) loadData(); });
        });
    </script>
@endsection