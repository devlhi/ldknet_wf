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
                                <a href="{{ url('admin/finance/report/cash-flows') }}" class="btn btn-secondary d-inline-block"><i class="uil-money-bill-stack"></i> Laporan Arus Kas</a>
                                <a href="{{ url('admin/finance/report/customers') }}" class="btn btn-primary d-inline-block"><i class="uil-users-alt"></i> Data Pelanggan</a>
                                <a href="{{ url('admin/finance/report/new/customers') }}" class="btn btn-secondary d-inline-block"><i class="uil-users-alt"></i> Data Pelanggan Baru</a>

                            </div><!-- end card-body -->
                        </div><!-- end card -->
                    </div><!-- end row -->
                </div> <!-- end col -->
            </div>


            <div class="row">
                <div class="col-xl-12">
                    <div class="row">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title">
                                    Filter Pelanggan
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Paket Internet : </label>
                                            <select class="form-control select2" name="paket" id="paket">
                                                <option value="Tampilkan Semua">Tampilkan Semua</option>

                                                @foreach ($getservice as $row)
                                                    <option value="{{ $row->paket }}">{{ $row->paket }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status : </label>
                                            <select class="form-control select2" name="status_pem" id="status_pem">
                                                <option>Tampilkan Semua</option>
                                                <option value="Active">Aktif</option>
                                                <option value="Isolir">Isolir</option>
                                                <option value="Berhenti">Berhenti</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <button class="btn btn-primary d-inline-block" id="filterButton">
                                    <i class="uil-search"></i> Filter
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
                                <h4 class="card-title">Data Pelanggan</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>ID Pelanggan</th>
                                                <th>Nama Pelanggan</th>
                                                <th>Email</th>
                                                <th>Nomor Whatsapp</th>
                                                <th>Alamat</th>
                                                <th>Paket</th>
                                                <th>Status</th>
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

        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection

@section('scripts')
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
                var paket = $("#paket").val();
                var status = $("#status_pem").val();

                showLoading();

                // Kirim permintaan filter ke controller dengan AJAX
                $.ajax({
                    url: "{{ url('admin/finance/report/customers/filter') }}",
                    type: "POST",
                    dataType: 'json',
                    data: {
                        paket: paket,
                        status: status,
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
                            filteredTable.html('<tr><td colspan="8">No data found</td></tr>');

                            $("#export").hide();

                        } else if (data.data.length > 0) {
                            $("#export").show();

                            var rowNum = 1;

                            $.each(data.data, function(index, row) {
                                // Buat baris baru dalam tabel

                                var newRow = '<tr>' +
                                    '<td>' + rowNum++ + '</td>' +
                                    '<td>' + esc(row.idpel) + '</td>' +
                                    '<td>' + esc(row.nama) + '</td>' +
                                    '<td>' + esc(row.email) + '</td>' +
                                    '<td>' + esc(row.nomor) + '</td>' +
                                    '<td>' + esc(row.alamat) + '</td>' +
                                    '<td>' + esc(row.paket) + '</td>' +
                                    '<td>' + esc(row.status) + '</td>' +
                                    '</tr>';
                                filteredTable.append(newRow);
                            });


                        } else {
                            // Tampilkan pesan jika data kosong
                            filteredTable.html('<tr><td colspan="8">No data found</td></tr>');
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
                    action: "{{ url('admin/finance/report/customers/export') }}",
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
