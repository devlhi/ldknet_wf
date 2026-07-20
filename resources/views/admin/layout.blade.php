<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- DataTables -->
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />

    <link href="{{ asset('assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/spectrum-colorpicker2/spectrum.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/libs/@chenfengyuan/datepicker/datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">

    <!-- SweetAlert2 -->
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    <!-- ANNORTY NET custom UI (efek gradient bulan pada card) -->
    <link href="{{ asset('assets/css/custom-ui.css') }}" rel="stylesheet" type="text/css" />

    @yield('css')
</head>

<body data-sidebar="light">

    <div id="layout-wrapper">

        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <!-- LOGO -->
                    <div class="navbar-brand-box">
                        <a href="{{ url('/') }}" class="logo logo-dark">
                            <span class="logo-sm">
                                <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="30">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="60">
                            </span>
                        </a>

                        <a href="{{ url('/') }}" class="logo logo-light">
                            <span class="logo-sm">
                                <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="30">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="60">
                            </span>
                        </a>
                    </div>

                    <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect vertical-menu-btn">
                        <i class="fa fa-fw fa-bars"></i>
                    </button>
                </div>

                <div class="d-flex">
                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img class="rounded-circle header-profile-user" src="{{ asset('assets/images/users/profile.svg') }}" alt="Header Avatar">
                            <span class="d-none d-xl-inline-block ms-1 fw-medium font-size-15">{{ auth()->user()->nama }}</span>
                            <i class="uil-angle-down d-none d-xl-inline-block font-size-15"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @if (auth()->user()->level !== 'technician')
                                <a class="dropdown-item d-block" href="{{ url('admin/account') }}"><i class="uil uil-cog font-size-18 align-middle me-1 text-muted"></i> <span class="align-middle"> Pengaturan</span></a>
                            @endif
                            <a class="dropdown-item" href="{{ url('auth/logout') }}"><i class="uil uil-sign-out-alt font-size-18 align-middle me-1 text-muted"></i> <span class="align-middle">Keluar</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ========== Left Sidebar Start ========== -->
        <div class="vertical-menu">

            <div class="navbar-brand-box">
                <a href="{{ url('admin/dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="30">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="60">
                    </span>
                </a>

                <a href="{{ url('admin/dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="30">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/logo/'.$logo) }}" alt="" height="60">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <div data-simplebar class="sidebar-menu-scroll">

                <div id="sidebar-menu">
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li class="menu-title">Menu</li>

                        @if (auth()->user()->level === 'technician')
                            <li>
                                <a href="{{ url('karyawan/absensi') }}" class="waves-effect">
                                    <i class="uil-calendar-alt"></i>
                                    <span>Absensi</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('karyawan/absensi/history') }}" class="waves-effect">
                                    <i class="uil-history"></i>
                                    <span>Riwayat Absensi</span>
                                </a>
                            </li>
                        @else

                        <li>
                            <a href="{{ url('admin/dashboard') }}" class="waves-effect">
                                <i class="uil-home-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="menu-title">CMS</li>

                        <li>
                            <a href="{{ url('admin/customers') }}" class="waves-effect" data-active-prefix="/admin/customer">
                                <i class="uil-users-alt"></i>
                                <span>Customers</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/cms/broadcast') }}" class="waves-effect">
                                <i class="uil-megaphone"></i>
                                <span>Broadcast Information</span>
                            </a>
                        </li>

                        <li class="menu-title">Service</li>

                        <li>
                            <a href="{{ url('admin/services') }}" class="waves-effect">
                                <i class="uil-list-ul"></i>
                                <span>Services</span>
                            </a>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-map"></i>
                                <span>Coverage</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/coverage/peta') }}">Peta Jaringan</a></li>
                                <li><a href="{{ url('admin/coverage/odp') }}">ODP</a></li>
                                <li><a href="{{ url('admin/coverage/customer') }}">Pelanggan</a></li>
                            </ul>
                        </li>

                        <li class="menu-title">Finance</li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-money-bill"></i>
                                <span>Cash Flow</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/finance/cash-flows/category') }}">Cash Category</a></li>
                                <li><a href="{{ url('admin/finance/cash-flows') }}">Cash Data</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="{{ url('admin/finance/report') }}" class="waves-effect" data-active-prefix="/admin/finance/report">
                                <i class="uil-invoice"></i>
                                <span>Report</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/finance/invoice') }}" class="waves-effect" data-active-prefix="/admin/finance/invoice">
                                <i class="uil-invoice"></i>
                                <span>Invoice Data</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/finance/invoice/generate') }}" class="waves-effect">
                                <i class="uil-invoice"></i>
                                <span>Generate Invoice</span>
                            </a>
                        </li>

                        <li class="menu-title">Akuntansi</li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-calculator-alt"></i>
                                <span>Accounting</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/accounting') }}">Dashboard</a></li>
                                <li><a href="{{ url('admin/accounting/accounts') }}">Daftar Akun</a></li>
                                <li><a href="{{ url('admin/accounting/journals') }}">Jurnal Umum</a></li>
                                <li><a href="{{ url('admin/accounting/contacts') }}">Kontak</a></li>
                                <li><a href="{{ url('admin/accounting/products') }}">Produk & Jasa</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-transaction"></i>
                                <span>Transaksi</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/accounting/sales') }}">Faktur Penjualan</a></li>
                                <li><a href="{{ url('admin/accounting/purchases') }}">Tagihan Pembelian</a></li>
                                <li><a href="{{ url('admin/accounting/expenses') }}">Biaya / Pengeluaran</a></li>
                                <li><a href="{{ url('admin/accounting/assets') }}">Aset Tetap</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-chart-pie"></i>
                                <span>Laporan Akuntansi</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/accounting/reports/ledger') }}">Buku Besar</a></li>
                                <li><a href="{{ url('admin/accounting/reports/trial-balance') }}">Neraca Saldo</a></li>
                                <li><a href="{{ url('admin/accounting/reports/profit-loss') }}">Laba Rugi</a></li>
                                <li><a href="{{ url('admin/accounting/reports/balance-sheet') }}">Neraca</a></li>
                                <li><a href="{{ url('admin/accounting/reports/cash-flow') }}">Arus Kas</a></li>
                            </ul>
                        </li>

                        <li class="menu-title">Admin Menu</li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-cog"></i>
                                <span>Pengaturan</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/setting/website') }}">Website</a></li>
                                <li><a href="{{ url('admin/setting/company') }}">Company</a></li>
                                <li><a href="{{ url('admin/setting/notification') }}">Setting Notification</a></li>
                                <li><a href="{{ url('admin/setting/cron') }}">Setting Cron</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="{{ url('admin/manage/user') }}" class="waves-effect">
                                <i class="uil-user"></i>
                                <span>User Management</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/manage/coupon') }}" class="waves-effect">
                                <i class="uil-percentage"></i>
                                <span>Coupon Management</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/gateway/payment') }}" class="waves-effect">
                                <i class="uil-credit-card"></i>
                                <span>Payment Gateway</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/gateway/payment/method') }}" class="waves-effect">
                                <i class="uil-credit-card"></i>
                                <span>Payment Method</span>
                            </a>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-envelope"></i>
                                <span>Email Gateway</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/gateway/email') }}">Setting</a></li>
                                <li><a href="{{ url('admin/gateway/email/message') }}">Test Message</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="uil-whatsapp"></i>
                                <span>Whatsapp Gateway</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ url('admin/whatsapp') }}">Setting</a></li>
                                <li><a href="{{ url('admin/whatsapp/message/text-message') }}">Test Message</a></li>
                                <li><a href="{{ url('admin/whatsapp/meta/templates') }}">Meta Templates</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="{{ url('admin/whatsapp/inbox') }}" class="waves-effect">
                                <i class="uil-chat"></i>
                                <span>Chat Whatsapp</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/gangguan') }}" class="waves-effect">
                                <i class="uil-exclamation-octagon"></i>
                                <span>Laporan Gangguan</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/template/message') }}" class="waves-effect">
                                <i class="uil-comment-alt-message"></i>
                                <span>Template Message</span>
                            </a>
                        </li>

                        <li class="menu-title">Server Menu</li>

                        <li>
                            <a href="{{ url('server/olt') }}" class="waves-effect">
                                <i class="uil-server"></i>
                                <span>OLT</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('server/router') }}" class="waves-effect">
                                <i class="uil-server-network"></i>
                                <span>Mikrotik</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('server/acs') }}" class="waves-effect">
                                <i class="uil-wifi"></i>
                                <span>ACS</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ url('admin/nms') }}" class="waves-effect">
                                <i class="uil-monitor"></i>
                                <span>NMS Monitor</span>
                            </a>
                        </li>

                        {{-- Menu Voucher Hotspot disembunyikan agar konsisten dgn CI4 (di CI4
                             voucher tidak ada di sidebar utama; diakses via URL langsung). Kode
                             controller/route/view tetap ada, tinggal kembalikan link ini bila
                             suatu saat menjual voucher hotspot. Route: server/voucher/dashboard --}}

                        @if (in_array(auth()->user()->level, ['admin', 'developer']))
                            <li class="menu-title">HRD / Karyawan</li>

                            <li>
                                <a href="{{ url('admin/karyawan') }}" class="waves-effect">
                                    <i class="uil-user-square"></i>
                                    <span>Data Karyawan</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ url('admin/absensi/rekap') }}" class="waves-effect">
                                    <i class="uil-calendar-alt"></i>
                                    <span>Rekap Absensi</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ url('admin/absensi/pengaturan') }}" class="waves-effect">
                                    <i class="uil-location-point"></i>
                                    <span>Pengaturan Radius Absen</span>
                                </a>
                            </li>
                        @endif

                        @endif

                    </ul>
                </div>
            </div>
        </div>
        <!-- Left Sidebar End -->

        <div class="main-content">
            @yield('content')
        </div>

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        © <script>document.write(new Date().getFullYear())</script> {{ $titletext }}
                    </div>
                </div>
            </div>
        </footer>

    </div>
    <!-- END layout-wrapper -->

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/waypoints/lib/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jquery.counterup/jquery.counterup.min.js') }}"></script>

    <!-- plugins -->
    <script src="{{ asset('assets/libs/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/libs/spectrum-colorpicker2/spectrum.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js') }}"></script>
    <script src="{{ asset('assets/libs/@chenfengyuan/datepicker/datepicker.min.js') }}"></script>

    <!-- Required datatable js -->
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Buttons examples -->
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('assets/js/pages/datatables.init.js') }}"></script>

    <!--tinymce js-->
    <script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-advanced.init.js') }}"></script>

    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    @yield('scripts')
</body>

</html>
