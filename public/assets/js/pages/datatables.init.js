/*
Template Name: Minible - Admin & Dashboard Template
Author: Themesbrand
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: Datatables Js File
*/

if (window.jQuery && $.fn.DataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            processing: '<span class="an-dt-ring"></span> Memuat...',
            emptyTable: 'Tidak ada data',
            zeroRecords: 'Data tidak ditemukan',
            lengthMenu: 'Tampilkan _MENU_ data',
            search: 'Cari:',
            paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
        }
    });
}

window.anTableLoading = function (target, show, text) {
    var el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return;

    var host = el.classList && el.classList.contains('dataTables_wrapper') ? el : (el.closest && el.closest('.dataTables_wrapper'));
    if (!host) {
        host = el.closest && el.closest('.table-responsive');
    }
    if (!host) {
        host = el.parentElement || el;
    }

    if (getComputedStyle(host).position === 'static') {
        host.style.position = 'relative';
    }

    var loader = host.querySelector(':scope > .an-table-loading');
    if (show) {
        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'an-table-loading';
            host.appendChild(loader);
        }
        loader.innerHTML = '<span class="an-dt-ring"></span> ' + (text || 'Memuat...');
    } else if (loader) {
        loader.remove();
    }
};

$(document).ready(function () {
    $('#datatable').DataTable({ processing: true });

    //Buttons examples
    var table = $('#datatable-buttons').DataTable({
        processing: true,
        lengthChange: false,
        buttons: ['copy', 'excel', 'pdf', 'colvis']
    });

    table.buttons().container()
        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');

    $(".dataTables_length select").addClass('form-select form-select-sm');
});

// Client-side DataTables yang diisi lewat $.ajax + rows.add() tidak memicu
// indikator processing bawaan. Global hook jQuery ajax menampilkan overlay
// yang sama pada tabel client-side tsb. Server-side pakai .dataTables_processing
// bawaan (sudah distyle global), jadi dilewati agar tidak dobel.
function anEachClientTable(cb) {
    if (!window.jQuery || !$.fn.DataTable) return;
    $('.dataTable').each(function () {
        if (!$.fn.DataTable.isDataTable(this)) return;
        var settings = $(this).DataTable().settings()[0];
        if (settings && settings.oFeatures && settings.oFeatures.bServerSide) return;
        cb(this);
    });
}

$(document).ajaxStart(function () {
    anEachClientTable(function (tbl) { window.anTableLoading(tbl, true); });
});

$(document).ajaxStop(function () {
    anEachClientTable(function (tbl) { window.anTableLoading(tbl, false); });
});

$(document).ajaxError(function () {
    anEachClientTable(function (tbl) { window.anTableLoading(tbl, false); });
});