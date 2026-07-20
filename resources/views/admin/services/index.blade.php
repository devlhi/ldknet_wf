@extends('admin.layout')

@section('css')
<style>
    .service-modal .modal-dialog {
        max-width: 560px;
    }

    .service-modal .modal-content {
        background: #fff !important;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }

    .service-modal .modal-header {
        background: #fff;
        border-bottom: 1px solid #eef2f7;
        color: #0f172a;
        padding: 16px 20px;
    }

    .service-modal .modal-title {
        align-items: center;
        color: #0f172a;
        display: flex;
        font-size: 16px;
        font-weight: 700;
        gap: 8px;
    }

    .service-modal .modal-title .mdi {
        align-items: center;
        background: #eff6ff;
        border-radius: 8px;
        color: #2563eb;
        display: inline-flex;
        height: 24px;
        justify-content: center;
        width: 24px;
    }

    .service-modal .btn-close {
        opacity: .7;
    }

    .service-modal .modal-body {
        background: #fff;
        padding: 20px;
    }

    .service-modal .form-label {
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .service-modal .form-control,
    .service-modal .form-select {
        border-color: #e2e8f0;
        padding: 9px 12px;
    }

    .service-modal .form-control:focus,
    .service-modal .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .service-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #eef2f7;
        padding: 14px 20px;
    }
</style>
@endsection

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
                        <h4 class="mb-0">Services</h4>

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

                        <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Add Service</button>
                    </div>

                    <div id="myModal" class="modal fade service-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ url('admin/services/add') }}" role="form" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="myModalLabel"><i class="mdi mdi-plus-box me-1"></i> Input Data Paket</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Paket</label>
                                            <input type="text" name="package" class="form-control" placeholder="Contoh: Paket Internet 100Mbps" required>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-7">
                                                <label class="form-label">Harga</label>
                                                <input type="number" name="price" class="form-control" placeholder="50000" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">PPN (%)</label>
                                                <input type="number" name="ppn" class="form-control" placeholder="10" value="0">
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-0">
                                            <div class="col-md-6">
                                                <label class="form-label">Mode</label>
                                                <select class="form-select" aria-label="Pilih Mode" name="mode" id="addMode" required>
                                                    <option value="">Pilih Mode</option>
                                                    <option value="hotspot">Hotspot</option>
                                                    <option value="pppoe">PPPOE</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Router</label>
                                                <select class="form-select" name="router" id="addRouter">
                                                    <option value="">Pilih Router</option>
                                                    @foreach ($router as $item)
                                                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mt-3" id="addProfileWrap" style="display:none">
                                            <label class="form-label">Profile MikroTik</label>
                                            <select class="form-select" name="profile" id="addProfile">
                                                <option value="">Pilih Profile</option>
                                            </select>
                                            <small class="text-muted">Profile akan diambil otomatis sesuai mode dan router yang dipilih.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                                        <button type="reset" class="btn btn-outline-danger"><i class="mdi mdi-refresh me-1"></i> Reset</button>
                                        <button type="submit" class="btn btn-success" name="add"><i class="mdi mdi-plus me-1"></i> Tambah</button>
                                    </div>
                                </form>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

                    <div class="card">
                        <div class="card-body">
                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Layanan</th>
                                        @if (auth()->user()->level === 'developer')
                                            <th>Profile </th>
                                            <th>Mode </th>
                                        @endif
                                        <th>Harga</th>
                                        <th>PPN</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php($i = 1)
                                    @foreach ($getServices as $row)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $row->paket }}</td>
                                            @if (auth()->user()->level === 'developer')
                                                <td>{{ $row->ppp_profile }}</td>
                                                <td>{{ $row->mode }}</td>
                                            @endif

                                            <td>Rp {{ number_format($row->harga) }}</td>
                                            <td>{{ $row->ppn }}%</td>
                                            <td>{{ $row->status }}</td>
                                            <td>
                                                <a href="{{ url('admin/services/edit/' . $row->id) }}" class="btn btn-sm btn-primary"><i class='uil uil-edit'></i> Edit</a>

                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i>Delete</button>
                                                @if (auth()->user()->level === 'developer')
                                                    <a href="{{ url('admin/services/sync/' . $row->id) }}" class="btn btn-sm btn-success"><i class='uil uil-sync'></i> Sinkronisasi Paket</a>
                                                @endif

                                                <!--- Modal Delete -->
                                                <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Layanan</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Apakah anda ingin menghapus Layanan <b><u>{{ $row->paket }}</u></b> ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <form action="{{ url('admin/services/delete/' . $row->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-primary">Yes</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($getServices->isEmpty())
                                        <tr><td colspan="8" class="text-center text-muted">Belum ada layanan.</td></tr>
                                    @endif
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
    $(function () {
        var profileUrl = @json(url('admin/services/sync/get_profiles'));
        var csrfToken = @json(csrf_token());
        var profileWrap = $('#addProfileWrap');
        var profile = $('#addProfile');

        function resetProfile(text) {
            profile.empty().append($('<option>', {
                value: '',
                text: text || 'Pilih Profile'
            }));
        }

        function loadProfiles() {
            var routerId = $('#addRouter').val();
            var mode = $('#addMode').val();

            resetProfile('Pilih Profile');

            if (!routerId || !mode) {
                profileWrap.hide();
                return;
            }

            profileWrap.fadeIn(150);
            resetProfile('Memuat profile...');

            $.ajax({
                url: profileUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    routerId: routerId,
                    mode: mode,
                    _token: csrfToken
                },
                success: function (response) {
                    profile.empty();

                    if (response.status !== 'success') {
                        resetProfile(response.message || 'Gagal mengambil profile');
                        return;
                    }

                    var rows = response.data || [];
                    if (!rows.length) {
                        resetProfile('Tidak ada profile yang ditemukan');
                        return;
                    }

                    resetProfile('Pilih Profile');
                    rows.forEach(function (item) {
                        if (!item.name) {
                            return;
                        }

                        profile.append($('<option>', {
                            value: item.name,
                            text: item.name
                        }));
                    });
                },
                error: function () {
                    resetProfile('Gagal mengambil data profile');
                }
            });
        }

        $('#addMode, #addRouter').on('change', loadProfiles);
        $('#myModal').on('hidden.bs.modal', function () {
            this.querySelector('form').reset();
            profileWrap.hide();
            resetProfile('Pilih Profile');
        });
    });
</script>
@endsection
