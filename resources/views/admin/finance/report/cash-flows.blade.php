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
                        <h4 class="mb-0">Laporan</h4>

                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="row">
                        <div class="card">
                            <div class="card-body">
                                <a href="{{ url('admin/finance/report') }}" class="btn btn-secondary d-inline-block"><i class="uil-invoice"></i> Laporan Tagihan</a>
                                <a href="{{ url('admin/finance/report/cash-flows') }}" class="btn btn-primary d-inline-block"><i class="uil-money-bill-stack"></i> Laporan Arus Kas</a>
                                <a href="{{ url('admin/finance/report/customers') }}" class="btn btn-secondary d-inline-block"><i class="uil-users-alt"></i> Data Pelanggan</a>
                                <a href="{{ url('admin/finance/report/new/customers') }}" class="btn btn-secondary d-inline-block"><i class="uil-users-alt"></i> Data Pelanggan Baru</a>

                            </div><!-- end card-body -->
                        </div><!-- end card -->
                    </div><!-- end row -->
                </div> <!-- end col -->
            </div>


            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title">
                                    Filter Arus Kas
                                </div>
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Periode : </label>
                                            <div class="row g-3">
                                                <div class="col-xl-6">
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
                                                <div class="col-xl-6">
                                                    <select class="form-select" aria-label="Default select example" name="tahun" id="tahun">
                                                        @foreach ($tahun as $row)
                                                            <option value="{{ $row->tahun }}" @selected((int) $row->tahun === 2026)>{{ $row->tahun }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Kategori Kas : </label>
                                            <select class="form-control select2" name="kategori" id="kategori">
                                                <option>Tampilkan Semua</option>
                                                @foreach ($katkas as $row)
                                                    <option value="{{ $row->nama }}">{{ $row->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis Kas : </label>
                                            <select class="form-control select2" name="jenis" id="jenis">
                                                <option>Tampilkan Semua</option>
                                                @php
                                                    // Menghapus duplikat berdasarkan kolom 'jenis' (port dari array_unique(array_column(...)))
                                                    $uniqueCategories = $katkas->pluck('jenis')->unique();
                                                @endphp
                                                @foreach ($uniqueCategories as $category)
                                                    <option value="{{ $category }}">{{ $category }}</option>
                                                @endforeach

                                            </select>
                                        </div>
                                    </div>

                                </div>
                                <br>
                                <button class="btn btn-primary d-inline-block" id="filterButton">
                                    <i class="uil-eye"></i> Terapkan Filter
                                </button>

                                <button class="btn btn-success d-inline-block" id="refreshButton" name="refresh">
                                    <i class="uil-refresh"></i> Refresh
                                </button>


                            </div><!-- end card-body -->
                        </div><!-- end card -->
                    </div><!-- end row -->
                </div> <!-- end col -->
            </div>



            <div class="row" id="filteredData" style="display: none;">
                <div class="col-xl-12">
                    <div class="row">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Data Arus Kas</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal Transaksi</th>
                                                <th>Kategori Kas</th>
                                                <th>Jenis Kas</th>
                                                <th>Jumlah</th>
                                                <th>Keterangan</th>
                                                <!-- Tambahkan kolom-kolom lain sesuai dengan kebutuhan Anda -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data hasil filter akan ditampilkan di sini -->
                                        </tbody>

                                    </table>

                                </div>
                                <br>

                                <button class="btn btn-primary" id="export" style="display: none; float: right;">
                                    <i class="fa fa-file-pdf"></i> Export Data Excel
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection

@section('scripts')
    <script>
        // Mendapatkan tanggal saat ini
        const currentDate = new Date();
        // Mendapatkan bulan saat ini (mulai dari 0 untuk Januari)
        const currentMonth = currentDate.getMonth() + 1; // +1 karena bulan dimulai dari 0

        // Temukan elemen select bulan
        const selectBulan = document.getElementById("bulan");

        // Atur opsi yang sesuai sebagai yang terpilih
        selectBulan.value = currentMonth;
    </script>


    {{-- jQuery sudah dimuat di admin.layout. Jangan muat ulang dari CDN karena
         bisa menimpa plugin yang sudah didaftarkan oleh layout. --}}

    <script>
        var base_url = '{{ url('/') }}/';
        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
    </script>

    <script>
        // Temukan tombol "Refresh" berdasarkan ID (jika Anda memberikan ID)
        const refreshButton = document.getElementById("refreshButton");

        // Tambahkan event listener untuk menangani klik tombol "Refresh"
        if (refreshButton) {
            refreshButton.addEventListener("click", function() {
                // Lakukan tindakan refresh halaman
                location.reload();
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            $("#filterButton").click(function() {
                var preloader = $('#preloader');

                // Fungsi untuk menampilkan loading screen
                function showLoading() {
                    preloader.show();
                }

                // Fungsi untuk menyembunyikan spinner loading
                function hideLoading() {
                    preloader.hide();
                }

                // Mengambil nilai dari form
                var bulan = $("#bulan").val();
                var tahun = $("#tahun").val();
                var kategori = $("#kategori").val();
                var jenis = $("#jenis").val();

                showLoading();

                // Inisialisasi total Pemasukan dan Pengeluaran
                var totalReceivedPemasukan = 0;
                var totalReceivedPengeluaran = 0;

                // Kirim permintaan filter ke controller dengan AJAX
                $.ajax({
                    url: "{{ url('admin/finance/report/cash-flows/filter') }}",
                    type: "POST",
                    dataType: 'json',
                    data: {
                        bulan: bulan,
                        tahun: tahun,
                        kategori: kategori,
                        jenis: jenis,
                    },
                    beforeSend: function() {
                        // Menambahkan tampilan spinner di dalam elemen #preloader
                        preloader.html('<div id="status"><div class="spinner"><i class="uil-shutter-alt spin-icon"></i></div></div>');
                    },
                    success: function(data) {
                        // Tampilkan tabel dengan data hasil filter
                        const filteredTable = $("#filteredData table tbody");
                        filteredTable.empty(); // Kosongkan isi tabel

                        if (typeof data.data === 'string' && data.data === 'No data found') {
                            // Tampilkan pesan jika data tidak ditemukan
                            filteredTable.html('<tr><td colspan="6">No data found</td></tr>');

                            $("#export").hide();

                        } else if (data.data.length > 0) {
                            $("#export").show();

                            var rowNum = 1;

                            $.each(data.data, function(index, row) {
                                // Buat baris baru dalam tabel
                                var rawDate = row.date;
                                var date = new Date(rawDate);
                                var options = {
                                    day: 'numeric',
                                    month: 'long',
                                    year: 'numeric'
                                };

                                var formattedDate = date.toLocaleDateString('id-ID', options);

                                var rawBalance = row.balance;
                                var rawTotal = parseFloat(row.balance);

                                if (row.jenis_kategori === 'Pemasukan') {
                                    statusText = '+';
                                    statusClass = 'btn-success';
                                    totalReceivedPemasukan += rawTotal;
                                } else if (row.jenis_kategori === 'Pengeluaran') {
                                    statusText = '-';
                                    statusClass = 'btn-danger';
                                    totalReceivedPengeluaran += rawTotal;
                                }

                                var curr = parseFloat(rawBalance);
                                var optioncur = {
                                    style: 'currency',
                                    currency: 'IDR'
                                };

                                var formattedRupiah = curr.toLocaleString('id-ID', optioncur);

                                var statusText, statusClass;

                                var newRow = '<tr>' +
                                    '<td>' + rowNum++ + '</td>' +
                                    '<td>' + formattedDate + '</td>' +
                                    '<td>' + esc(row.category) + '</td>' +
                                    '<td>' + esc(row.jenis_kategori) + '</td>' +
                                    '<td><span class="btn btn-sm ' + statusClass + '">' + statusText + ' ' + formattedRupiah + '</span></td>' +
                                    '<td>' + esc(row.asal) + '</td>' +
                                    '</tr>';
                                filteredTable.append(newRow);
                            });

                            // Setelah loop selesai, hitung hasil pengurangan dan tambahkan baris total ke tabel
                            var totalReceived = totalReceivedPemasukan - totalReceivedPengeluaran;

                            // Menambahkan baris total Pemasukan di akhir tabel
                            var totalRowPemasukan = '<tr>' +
                                '<td></td>' +
                                '<td colspan="2"><strong>Pemasukan</strong></td>' +
                                '<td></td>' +
                                '<td><strong>' + formatRupiah(totalReceivedPemasukan) + '</strong></td>' +
                                '<td></td>' +
                                '</tr>';
                            filteredTable.append(totalRowPemasukan);

                            // Menambahkan baris total Pengeluaran di akhir tabel
                            var totalRowPengeluaran = '<tr>' +
                                '<td></td>' +
                                '<td colspan="2"><strong>Pengeluaran</strong></td>' +
                                '<td></td>' +
                                '<td><strong>' + formatRupiah(totalReceivedPengeluaran) + '</strong></td>' +
                                '<td></td>' +
                                '</tr>';
                            filteredTable.append(totalRowPengeluaran);

                            // Menambahkan baris hasil pengurangan di akhir tabel
                            var totalRowHasilPengurangan = '<tr>' +
                                '<td></td>' +
                                '<td></td>' +
                                '<td colspan="1"><strong>Total Akhir</strong></td>' +
                                '<td></td>' +
                                '<td></td>' +
                                '<td><strong>' + formatRupiah(totalReceived) + '</strong></td>' +
                                '</tr>';
                            filteredTable.append(totalRowHasilPengurangan);

                        } else {
                            // Tampilkan pesan jika data kosong
                            filteredTable.html('<tr><td colspan="6">No data found</td></tr>');
                            $("#export").hide();

                        }

                        // Sembunyikan spinner setelah permintaan AJAX selesai (jika data ditemukan atau tidak)
                        hideLoading();
                        $("#filteredData").css("display", "block");
                    },
                    error: function(xhr, status, error) {
                        console.error(error);

                        // Sembunyikan spinner jika ada kesalahan
                        hideLoading();
                    }
                });
            });

        });


        function formatRupiah(angka) {
            var number_string = angka.toString();
            var split = number_string.split(',');
            var sisa = split[0].length % 3;
            var rupiah = split[0].substr(0, sisa);
            var ribuan = split[0].substr(sisa).match(/\d{3}/g);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return 'Rp ' + rupiah;
        }
    </script>


    <script>
        $(document).ready(function() {
            $("#export").click(function() {
                // Mengambil data yang ada dalam tabel dengan ID "filteredData"
                const table = document.querySelector("#filteredData table");

                // Menginisialisasi array untuk menyimpan data
                const dataToExport = [];

                // Mengambil baris dan kolom dari tabel HTML
                const rows = table.querySelectorAll("tr");

                rows.forEach(function(row) {
                    const rowData = [];
                    const cells = row.querySelectorAll("td, th");

                    cells.forEach(function(cell) {
                        rowData.push(cell.textContent.trim());
                    });

                    // Menambahkan baris data ke array dataToExport
                    dataToExport.push(rowData);
                });

                // Submit via form POST agar file di-stream lewat route terproteksi
                // auth (tidak disimpan ke public/laporan).
                var form = $('<form>', {
                    method: 'POST',
                    action: "{{ url('admin/finance/report/cash-flows/export') }}",
                    style: 'display:none'
                });
                form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                form.append($('<input>', { type: 'hidden', name: 'dataToExport', value: JSON.stringify(dataToExport) }));
                $('body').append(form);
                form.trigger('submit');
                form.remove();
            });
        });
    </script>
@endsection
