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
                        <h4 class="mb-0">Cash Flows </h4>

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

                    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div class="float-left">
                                        <h6 class="modal-title" id="custom-width-modalLabel">Tambah Data Kas</h6>
                                    </div>
                                    <div class="float-right">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                </div>
                                <div class="modal-body">
                                    <form class="form-horizontal" action="{{ url('admin/finance/cash-flows/add') }}" role="form" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Kategori</label>
                                            <select class="form-select" id="floatingSelectGrid" name="category" aria-label="Floating label select example">
                                                <option selected disabled>Pilih salah satu</option>
                                                @foreach ($getDataJenis as $datajenis)
                                                    <option>{{ $datajenis->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Jenis Kategori</label>
                                            <select class="form-select" id="floatingSelectGrid" name="jenis" aria-label="Floating label select example">
                                                <option selected disabled>Pilih salah satu</option>
                                                <option value="Pemasukan">Pemasukan</option>
                                                <option value="Pengeluaran">Pengeluaran</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Jumlah</label>
                                            <input type="number" class="form-control" id="formrow-Fullname-input" name="jumlah" placeholder="Masukan Jumlah">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Keterangan</label>
                                            <input type="text" class="form-control" id="formrow-Fullname-input" name="asal" placeholder="Masukan Catatan">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="formrow-Fullname-input">Tanggal</label>
                                            <input class="form-control flatpickr1" name="date" id="example-date-input" type="date" placeholder="Silahkan pilih tanggal">
                                        </div>

                                        <div class="d-flex flex-wrap gap-3 mt-3">
                                            <button type="submit" class="btn btn-primary waves-effect waves-light w-md">Submit</button>
                                            <button type="reset" class="btn btn-outline-danger waves-effect waves-light w-md">Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

                    <div class="row">
                        <!-- Tampilkan statistik berdasarkan filter bulan dan tahun -->
                        <!-- Tambahkan ID pada elemen yang ingin diperbarui -->
                        <div class="col-md-6 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="float-end mt-2">
                                        <div id="total-revenue-chart"></div>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 mt-1"><span id="total-revenue" data-plugin="counterup">{{ number_format($getDataPemasukan) }}</span></h4>
                                        <p class="text-muted mb-0">Total Pemasukan</p>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->

                        <!-- Tambahkan ID pada elemen yang ingin diperbarui -->
                        <div class="col-md-6 col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="float-end mt-2">
                                        <div id="orders-chart"></div>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 mt-1"><span id="invoice-paid" data-plugin="counterup">{{ number_format($getDataPengeluaran) }}</span></h4>
                                        <p class="text-muted mb-0">Total Pengeluaran</p>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->

                        <!-- Tambahkan ID pada elemen yang ingin diperbarui -->
                        <div class="col-md col-xl-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="float-end mt-2">
                                        <div id="customers-chart"></div>
                                    </div>
                                    <div>
                                        @php $bersih = $getDataPemasukan - $getDataPengeluaran; @endphp
                                        <h4 class="mb-1 mt-1"><span id="data-bersih" data-plugin="counterup">{{ number_format($bersih) }}</span></h4>
                                        <p class="text-muted mb-0">Total Pendapatan Bersih</p>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col-->



                        <div>

                            <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Data Kas</button>
                        </div>




                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">
                            <div class="table-responsive">

                                {{-- ID unik (bukan #datatable) supaya tidak di-auto-init ganda
                                     oleh assets/js/pages/datatables.init.js -> "Cannot reinitialise". --}}
                                <table id="datatable-cashflows" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No </th>
                                            <th>Tanggal</th>
                                            <th>Kategori</th>
                                            <th>Jenis Kategori</th>

                                            <th>Jumlah</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>


                                    </tbody>
                                </table>
                            </div>
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
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        function formatRupiah(angka) {
            return parseFloat(angka || 0).toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
        }

        $(function () {
            $('#bulan').val(String(new Date().getMonth() + 1));

            var table = null;
            var activated = false;

            function initializeTable() {
                if (table) {
                    return table;
                }

                table = $('#datatable-cashflows').DataTable({
                    processing: true,
                    language: {
                        processing: '<span class="an-dt-ring"></span> Memuat...',
                        emptyTable: 'Tidak ada data kas',
                        zeroRecords: 'Data tidak ditemukan',
                        info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                        infoEmpty: 'Menampilkan 0 data',
                        infoFiltered: '(disaring dari _MAX_ total)',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        search: 'Cari:',
                        paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                    },
                    columns: [
                        { data: null, render: function(data, type, row, meta) { return meta.row + 1; } },
                        {
                            data: 'date',
                            render: function(data) {
                                return new Date(data).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                            }
                        },
                        { data: 'category' },
                        { data: 'jenis_kategori' },
                        {
                            data: 'balance',
                            render: function(data, type, row) {
                                var label = 'secondary';
                                var text = '';
                                if (row.jenis_kategori === 'Pemasukan') {
                                    label = 'success';
                                    text = '+';
                                } else if (row.jenis_kategori === 'Pengeluaran') {
                                    label = 'danger';
                                    text = '-';
                                }
                                return '<span class="badge bg-' + label + '">' + text + ' ' + formatRupiah(data) + '</span>';
                            }
                        },
                        { data: 'asal' }
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
                    $.ajax({
                        url: '{{ url('finance/cash-flows/filter/getdata') }}',
                        type: 'POST',
                        dataType: 'json',
                        data: { show_data: 1, bulan: bulan, tahun: tahun }
                    }),
                    $.ajax({
                        url: '{{ url('finance/cash-flows/filter/ambil-data') }}/' + bulan + '/' + tahun,
                        type: 'GET',
                        dataType: 'json',
                        data: { show_data: 1 }
                    })
                ).done(function(rowsResponse, statsResponse) {
                    var stats = statsResponse[0];
                    dataTable.clear().rows.add(rowsResponse[0]).draw();
                    $('#total-revenue').text(formatRupiah(stats.getDataPemasukan));
                    $('#invoice-paid').text(formatRupiah(stats.getDataPengeluaran));
                    $('#data-bersih').text(formatRupiah((parseFloat(stats.getDataPemasukan) || 0) - (parseFloat(stats.getDataPengeluaran) || 0)));
                    activated = true;
                }).fail(function(xhr, status, error) {
                    console.error(error);
                }).always(function() {
                    button.prop('disabled', false).html('<i class="uil uil-eye me-1"></i> Tampilkan Data');
                });
            }

            $('#showDataButton').on('click', loadData);
            $('#bulan, #tahun').on('change', function() {
                if (activated) {
                    loadData();
                }
            });
        });
    </script>
@endsection