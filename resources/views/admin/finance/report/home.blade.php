@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0"><i class="uil uil-file-chart-alt me-1"></i> Laporan</h4>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('admin/finance/report') }}" class="btn btn-primary"><i class="uil-invoice me-1"></i> Laporan Tagihan</a>
                    <a href="{{ url('admin/finance/report/cash-flows') }}" class="btn btn-outline-secondary"><i class="uil-money-bill-stack me-1"></i> Laporan Arus Kas</a>
                    <a href="{{ url('admin/finance/report/customers') }}" class="btn btn-outline-secondary"><i class="uil-users-alt me-1"></i> Data Pelanggan</a>
                    <a href="{{ url('admin/finance/report/new/customers') }}" class="btn btn-outline-secondary"><i class="uil-user-plus me-1"></i> Data Pelanggan Baru</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0"><i class="uil uil-filter me-1"></i> Filter Tagihan</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Paket Internet</label>
                        <select class="form-select select2" name="paket" id="paket">
                            <option value="Pilih Paket">Pilih Paket</option>
                            @foreach ($getservice as $row)
                                <option value="{{ $row->paket }}">{{ $row->paket }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select select2" name="status_pem" id="status_pem">
                            <option value="Tampilkan Semua">Tampilkan Semua</option>
                            <option value="Paid">Sudah Terbayar</option>
                            <option value="Unpaid">Belum Terbayar</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Pembayaran</label>
                        <select class="form-select select2" name="penerima" id="penerima">
                            <option value="Tampilkan Semua">Tampilkan Semua</option>
                            <option value="Pembayaran Tunai">Pembayaran Tunai</option>
                            <option value="Pembayaran Non Tunai">Pembayaran Non Tunai</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Periode</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <select class="form-select" name="bulan" id="bulan">
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
                            <div class="col-6">
                                <select class="form-select" name="tahun" id="tahun">
                                    @foreach ($tahun as $row)
                                        <option value="{{ $row->tahun }}" @selected((int) $row->tahun === 2026)>{{ $row->tahun }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary" id="filterButton"><i class="uil-eye me-1"></i> Terapkan Filter</button>
                    <button class="btn btn-outline-secondary" id="refreshButton"><i class="uil-refresh me-1"></i> Refresh</button>
                </div>
            </div>
        </div>

        <div class="row" id="filteredData" style="display: none;">
            <div class="col-xl-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Data Tagihan</h5>
                        <button class="btn btn-success btn-sm" id="export" style="display: none;">
                            <i class="fa fa-file-excel me-1"></i> Export Excel
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Pelanggan</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Paket</th>
                                        <th>Periode</th>
                                        <th>Tagihan</th>
                                        <th>Pembayaran</th>
                                        <th>Status</th>
                                        <th class="text-center">Bukti Pembayaran</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1;
    document.getElementById("bulan").value = currentMonth;
</script>

<script>
    var base_url = '{{ url("/") }}/';
    function esc(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });

    const refreshButton = document.getElementById("refreshButton");
    if (refreshButton) {
        refreshButton.addEventListener("click", function() { location.reload(); });
    }
</script>

<script>
    $(document).ready(function() {
        $("#filterButton").click(function() {
            const button = $(this);
            const filteredTable = $("#filteredData table tbody");

            var paket = $("#paket").val();
            var status = $("#status_pem").val();
            var penerima = $("#penerima").val();
            var bulan = $("#bulan").val();
            var tahun = $("#tahun").val();

            $("#filteredData").show();
            $("#export").hide();
            filteredTable.html('<tr><td colspan="9" class="text-center text-muted py-3"><i class="uil uil-spinner-alt spin-icon me-1"></i> Memuat data...</td></tr>');
            button.prop('disabled', true).html('<i class="uil uil-spinner-alt spin-icon me-1"></i> Memuat...');

            $.ajax({
                url: "{{ url('admin/finance/report/filter') }}",
                type: "POST",
                dataType: 'json',
                data: { paket: paket, status: status, penerima: penerima, bulan: bulan, tahun: tahun },
                success: function(data) {
                    filteredTable.empty();

                    if (typeof data.data === 'string' && data.data === 'No data found') {
                        filteredTable.html('<tr><td colspan="9" class="text-center text-muted py-3">Tidak ada data ditemukan</td></tr>');
                        $("#export").hide();
                    } else if (data.data.length > 0) {
                        $("#export").show();

                        var rowNum = 1;
                        var totalReceived = 0;

                        $.each(data.data, function(index, row) {
                            var rawDate = row.date;
                            var date = new Date(rawDate);
                            var options = { year: 'numeric', month: 'long' };
                            var formattedDate = date.toLocaleDateString('id-ID', options);

                            // Kolom "Tagihan" harus menampilkan nominal invoice (price),
                            // bukan received. Untuk Unpaid, received = 0 sehingga laporan
                            // piutang sebelumnya selalu tampil Rp 0.
                            var rawtagihan = parseFloat(row.price || 0);
                            totalReceived += rawtagihan;

                            var curr = rawtagihan;
                            var optioncur = { style: 'currency', currency: 'IDR' };
                            var formattedRupiah = curr.toLocaleString('id-ID', optioncur);

                            var currency = parseFloat(totalReceived);
                            var formatRupiah = currency.toLocaleString('id-ID', optioncur);

                            var statusText, statusClass;
                            if (row.status === 'Paid') {
                                statusText = 'Sudah Terbayar';
                                statusClass = 'btn-success';
                            } else if (row.status === 'Unpaid') {
                                statusText = 'Belum Terbayar';
                                statusClass = 'btn-danger';
                            }

                            var link = '';
                            if (row.status === 'Unpaid') {
                                link = '<span class="text-muted small">Belum melakukan pembayaran</span>';
                            } else if (row.update_by !== 'System Payment Gateway Tripay') {
                                if (row.bukti_pembayaran) {
                                    link = '<a href="{{ url("data/bukti") }}/' + row.bukti_pembayaran + '" target="_blank" class="btn btn-sm btn-info text-white"><i class="uil uil-image me-1"></i> Lihat Bukti</a>';
                                } else {
                                    link = '<span class="text-muted small">Tidak ada bukti</span>';
                                }
                            } else {
                                link = '<span class="badge bg-secondary"><i class="uil uil-credit-card me-1"></i> Auto Payment Gateway</span>';
                            }

                            var newRow = '<tr>' +
                                '<td>' + rowNum++ + '</td>' +
                                '<td>' + esc(row.idpel) + '</td>' +
                                '<td>' + esc(row.nama) + '</td>' +
                                '<td>' + esc(row.package) + '</td>' +
                                '<td>' + formattedDate + '</td>' +
                                '<td>' + formattedRupiah + '</td>' +
                                '<td>' + esc(row.update_by) + '</td>' +
                                '<td><span class="btn btn-sm ' + statusClass + '">' + statusText + '</span></td>' +
                                '<td class="text-center">' + link + '</td>' +
                                '</tr>';
                            filteredTable.append(newRow);
                        });

                        var totalRow = '<tr class="table-light">' +
                            '<td></td>' +
                            '<td colspan="4"><strong>Total Tagihan</strong></td>' +
                            '<td><strong>' + formatRupiah + '</strong></td>' +
                            '<td></td>' +
                            '<td></td>' +
                            '<td></td>' +
                            '</tr>';
                        filteredTable.append(totalRow);
                    } else {
                        filteredTable.html('<tr><td colspan="9" class="text-center text-muted py-3">Tidak ada data ditemukan</td></tr>');
                        $("#export").hide();
                    }

                    button.prop('disabled', false).html('<i class="uil-eye me-1"></i> Terapkan Filter');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    filteredTable.html('<tr><td colspan="9" class="text-center text-danger py-3">Gagal memuat data. Silakan refresh halaman lalu coba lagi.</td></tr>');
                    button.prop('disabled', false).html('<i class="uil-eye me-1"></i> Terapkan Filter');
                }
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
    });
</script>

<script>
    $(document).ready(function() {
        $("#export").click(function() {
            const table = document.querySelector("#filteredData table");
            const dataToExport = [];
            const rows = table.querySelectorAll("tr");

            rows.forEach(function(row) {
                const rowData = [];
                const cells = row.querySelectorAll("td, th");
                cells.forEach(function(cell) { rowData.push(cell.textContent.trim()); });
                dataToExport.push(rowData);
            });

            var form = $('<form>', {
                method: 'POST',
                action: "{{ url('admin/finance/report/export') }}",
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
