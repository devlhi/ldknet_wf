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
    {{-- jQuery + DataTables sudah dimuat di layout. Jangan muat jQuery lagi dari CDN:
         itu menimpa $ dan menghapus registrasi $.fn.DataTable sehingga tabel gagal
         render (tbody kosong). exceljs juga dihapus — tidak ada tombol export di sini. --}}
    <script>
        var base_url = '{{ url('/') }}/';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        // Default dropdown Bulan ke bulan berjalan (dropdown Tahun sudah terisi
        // dari DB via getTahunMasuk(); default ke opsi terakhir = tahun terbaru).
        $(function () {
            $('#bulan').val(String(new Date().getMonth() + 1));
        });
    </script>

    <script>
        $(document).ready(function() {
            // Inisialisasi datatables saat halaman dimuat pertama kali
            var table = $('#datatable-invoices').DataTable({
                language: {
                    emptyTable: 'Tidak ada data invoice',
                    zeroRecords: 'Data tidak ditemukan',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ invoice',
                    infoEmpty: 'Menampilkan 0 invoice',
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
                        data: 'idpel',
                        render: function(data, type, row) {
                            return row.idpel
                        }
                    },
                    {
                        data: 'code',
                        render: function(data, type, row) {
                            return row.code
                        }
                    },
                    {
                        data: 'nama',
                        render: function(data, type, row) {
                            return row.nama
                        }
                    },
                    {
                        // Link cetak/lihat invoice (port dari CI4: kolom "Print").
                        data: 'code',
                        orderable: false,
                        render: function(data, type, row) {
                            var cls = row.status === 'Paid' ? 'btn-success' : 'btn-danger';
                            return '<a href="' + base_url + 'admin/finance/invoice/print/' + row.code +
                                '" target="_blank" class="btn btn-sm ' + cls + '"><i class="uil uil-print"></i> Lihat</a>';
                        }
                    },
                    {
                        data: 'package',
                        render: function(data, type, row) {
                            return row.package
                        }
                    },
                    {
                        data: 'price',
                        render: function(data, type, row) {
                            // Ubah format angka menjadi format yang lebih mudah dibaca
                            return parseFloat(data).toLocaleString('id-ID', {
                                style: 'currency',
                                currency: 'IDR'
                            });
                        }
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {

                            var statusText, statusClass;

                            if (row.status === 'Paid') {
                                statusText = 'Sudah Terbayar';
                                statusClass = 'bg-success';
                            } else if (row.status === 'Unpaid') {
                                statusText = 'Belum Terbayar';
                                statusClass = 'bg-danger';
                            } else {
                                statusText = row.status;
                                statusClass = 'bg-secondary';
                            }

                            return '<span class="badge ' + statusClass + '">' + statusText + '</span>';
                        }
                    },
                    {
                        data: 'date',
                        render: function(data, type, row) {
                            // Ubah format data dari server menjadi format tanggal yang diinginkan
                            var dateObj = new Date(data);
                            var options = {
                                month: 'long',
                                year: 'numeric'
                            };
                            return dateObj.toLocaleDateString('id-ID', options);
                        }
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {

                            if (row.status === 'Unpaid') {
                                return '<a href="' + base_url + 'admin/finance/invoice/edit/' + row.code + '" class="btn btn-sm btn-primary"><i class="uil uil-edit"></i> <strong>Edit Data</strong></a>';
                            } else {
                                return ''; // atau kembalikan string kosong jika tidak ada tindakan untuk status lain
                            }
                        }
                    },

                ]
            });

            var preloader = $('#preloader');

            // Fungsi untuk menampilkan loading screen
            function showLoading() {
                preloader.show();
            }

            // Fungsi untuk menyembunyikan spinner loading
            function hideLoading() {
                preloader.hide();
            }

            // Fungsi untuk mengambil data tanpa filter saat halaman dimuat pertama kali
            function fetchInitialData() {
                $.ajax({
                    url: '{{ url('finance/invoice/filter/getdata') }}',
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
            fetchInitialData();

            // Event saat elemen #bulan atau #tahun berubah
            $('#bulan, #tahun').on('change', function() {
                var bulan = $('#bulan').val();
                var tahun = $('#tahun').val();

                // Permintaan Ajax untuk mengambil data berdasarkan filter
                $.ajax({
                    url: '{{ url('finance/invoice/filter/getdata') }}',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        bulan: bulan,
                        tahun: tahun
                    },
                    beforeSend: function() {
                        // Menambahkan tampilan spinner di dalam elemen #preloader
                        preloader.html('<div id="status"><div class="spinner"><i class="uil-shutter-alt spin-icon"></i></div></div>');
                        showLoading();
                    },

                    success: function(data) {
                        // Bersihkan dan muat ulang datatables dengan data yang baru
                        table.clear().rows.add(data).draw();
                        hideLoading();

                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        hideLoading();

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
                url: '{{ url('finance/invoice/filter/ambil-data') }}/' + selectedBulan + '/' + selectedTahun,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Perbarui total pendapatan


                    // Perbarui total pembayaran terbayar
                    if (data.getInvoicePaid !== null) {
                        var formattedInvoicePaid = data.getInvoicePaid;
                        $('#invoice-paid').text(formattedInvoicePaid);
                    } else {
                        $('#invoice-paid').text('Tidak Tersedia'); // Atau nilai lain yang sesuai
                    }

                    // Perbarui total pembayaran belum terbayar
                    if (data.getInvoiceUnpaid !== null) {
                        var formattedInvoiceUnpaid = data.getInvoiceUnpaid;
                        $('#invoice-unpaid').text(formattedInvoiceUnpaid);
                    } else {
                        $('#invoice-unpaid').text('Tidak Tersedia'); // Atau nilai lain yang sesuai
                    }
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
