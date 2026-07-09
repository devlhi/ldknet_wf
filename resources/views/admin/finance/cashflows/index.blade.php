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
    {{-- jQuery + DataTables sudah dimuat di layout. Jangan muat jQuery lagi dari CDN:
         itu menimpa $ dan menghapus registrasi $.fn.DataTable sehingga tabel gagal render. --}}
    <script>
        var base_url = '{{ url('/') }}/';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        // Default dropdown Bulan ke bulan berjalan.
        $(function () {
            $('#bulan').val(String(new Date().getMonth() + 1));
        });
    </script>

    <script>
        $(document).ready(function() {

            var preloader = $('#preloader');

            // Fungsi untuk menampilkan loading screen
            function showLoading() {
                $('#preloader').show();
            }

            // Fungsi untuk menyembunyikan spinner loading
            function hideLoading() {
                $('#preloader').hide();
            }


            // Inisialisasi datatables saat halaman dimuat pertama kali
            var table = $('#datatable-cashflows').DataTable({
                language: {
                    emptyTable: 'Tidak ada data kas',
                    zeroRecords: 'Data tidak ditemukan',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 data',
                    infoFiltered: '(disaring dari _MAX_ total)',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    search: 'Cari:',
                    paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                },
                // Konfigurasi kolom datatables sesuai dengan struktur tabel
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1; // Mengembalikan nomor urut berdasarkan baris
                        }
                    },
                    {
                        data: 'date',
                        render: function(data, type, row) {
                            // Ubah format data dari server menjadi format tanggal yang diinginkan
                            var dateObj = new Date(data);
                            var options = {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            };
                            return dateObj.toLocaleDateString('id-ID', options);
                        }
                    },
                    {
                        data: 'category',
                        render: function(data, type, row) {
                            return row.category;

                        }
                    },
                    {
                        data: 'jenis_kategori',
                        render: function(data, type, row) {
                            return row.jenis_kategori;

                        }
                    },
                    {
                        data: 'balance',
                        render: function(data, type, row) {
                            var label = 'secondary', text = '';
                            if (row.jenis_kategori == 'Pemasukan') {
                                label = 'success';
                                text = '+';
                            } else if (row.jenis_kategori == 'Pengeluaran') {
                                label = 'danger';
                                text = '-';
                            }

                            var formattedBalance = parseFloat(data).toLocaleString('id-ID', {
                                style: 'currency',
                                currency: 'IDR'
                            });

                            return '<span class="badge bg-' + label + '">' + text + ' ' + formattedBalance + '</span>';
                        }

                    },
                    {
                        data: 'asal',
                        render: function(data, type, row) {

                            return row.asal;
                        }
                    },

                ]
            });

            // Fungsi untuk mengambil data tanpa filter saat halaman dimuat pertama kali
            function fetchInitialData() {
                $.ajax({
                    url: '{{ url('finance/cash-flows/filter/getdata') }}',
                    type: 'POST',
                    dataType: 'json',
                    success: function(data) {
                        // Isi tabel dengan data tanpa filter
                        table.rows.add(data).draw();
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }

            // Panggil fungsi untuk mengambil data tanpa filter saat halaman dimuat pertama kali
            showLoading();
            fetchInitialData();
            hideLoading();


            // Event saat elemen #bulan atau #tahun berubah
            $('#bulan, #tahun').on('change', function() {
                showLoading();

                var bulan = $('#bulan').val();
                var tahun = $('#tahun').val();

                // Permintaan Ajax untuk mengambil data berdasarkan filter
                $.ajax({
                    url: '{{ url('finance/cash-flows/filter/getdata') }}',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        bulan: bulan,
                        tahun: tahun
                    },

                    beforeSend: function() {
                        // Menambahkan tampilan spinner di dalam elemen #preloader
                        $('#preloader').html('<div id="status"><div class="spinner"><i class="uil-shutter-alt spin-icon"></i></div></div>');
                    },


                    success: function(data) {
                        // Bersihkan dan muat ulang datatables dengan data yang baru
                        table.clear().rows.add(data).draw();
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    },
                    complete: function() {
                        hideLoading(); // Sembunyikan spinner setelah permintaan AJAX selesai
                    }
                });

            });
        });
    </script>

    <!-- Tambahkan script berikut untuk mengatur interaksi dengan filter bulan dan tahun -->
    <script>
        // Fungsi untuk mengambil data statistik terbaru menggunakan AJAX dengan filter bulan dan tahun
        function ambilDataStatistik() {
            var selectedBulan = $('#bulan').val();
            var selectedTahun = $('#tahun').val();

            $.ajax({
                url: '{{ url('finance/cash-flows/filter/ambil-data') }}/' + selectedBulan + '/' + selectedTahun,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Perbarui total pendapatan
                    if (data.getDataPemasukan !== null) {
                        var formattedAllCredit = formatRupiah(data.getDataPemasukan);
                        $('#total-revenue').text(formattedAllCredit);
                    } else {
                        $('#total-revenue').text('Rp 0'); // Tampilkan Rp 0 jika tidak ada data pemasukan
                    }

                    // Perbarui total pembayaran terbayar
                    if (data.getDataPengeluaran !== null) {
                        var formattedInvoicePaid = formatRupiah(data.getDataPengeluaran);
                        $('#invoice-paid').text(formattedInvoicePaid);
                    } else {
                        $('#invoice-paid').text('Rp 0'); // Tampilkan Rp 0 jika tidak ada data pengeluaran
                    }


                    // Hitung dan perbarui data bersih (selisih antara pemasukan dan pengeluaran)
                    var dataPemasukan = parseFloat(data.getDataPemasukan) || 0;
                    var dataPengeluaran = parseFloat(data.getDataPengeluaran) || 0;
                    var dataBersih = dataPemasukan - dataPengeluaran;
                    var formattedDataBersih = formatRupiah(dataBersih);
                    $('#data-bersih').text(formattedDataBersih);


                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }

        // Fungsi untuk menangani perubahan filter bulan dan tahun
        function filterData() {
            ambilDataStatistik();
        }

        // Ambil statistik awal ketika halaman dimuat
        $(document).ready(function() {
            // Panggil fungsi filterData untuk mengambil statistik berdasarkan filter bulan dan tahun yang terpilih
            filterData();

            // Tambahkan event listener untuk mengaktifkan pemanggilan filterData setiap kali filter bulan atau tahun berubah
            $('#bulan, #tahun').change(function() {
                filterData();
            });
        });

        // Fungsi untuk mengonversi angka menjadi format Rupiah
        function formatRupiah(angka) {
            return parseFloat(angka).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });
        }
    </script>
@endsection
