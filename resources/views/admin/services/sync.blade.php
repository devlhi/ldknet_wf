@extends('admin.layout')

@section('content')
<!-- ============================================================== -->
<!-- Start right Content here -->
<!-- ============================================================== -->
@php($current = $content[0] ?? null)
@foreach ($content as $row)

    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Sinkronisasi Services</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            @if (session('auth_errors'))
                                <div class="alert alert-danger alert-message" role="alert">
                                    @foreach (session('auth_errors') as $err)
                                        {{ $err }}
                                    @endforeach
                                </div>
                            @endif

                            <form method="post" action="{{ url('admin/services/sync/update') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Paket</label>
                                            <input type="hidden" name="target" value="{{ $row->id }}">
                                            <input type="text" name="nama" class="form-control" value="{{ $row->paket }}" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Mode</label>
                                            <input type="text" id="mode" class="form-control" value="{{ $mode }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Router</label>
                                            <select class="form-select" id="router" name="router" required>
                                                <option value="" selected disabled>Silahkan pilih router</option>
                                                @foreach ($router as $item)
                                                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="pppprofile" style="display:none">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Profile</label>
                                            <select class="form-select" id="profile" name="profile">
                                                <option value="">Pilih Profile</option>
                                            </select>
                                            @if ($row->ppp_profile)
                                                <small class="text-muted">Profile tersimpan saat ini: <b>{{ $row->ppp_profile }}</b></small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-primary" type="submit">Update</button>
                            </form>
                        </div>
                    </div>
                    <!-- end card -->
                </div> <!-- end col -->
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('scripts')
<script>
    $(function () {
        var currentProfile = @json($current->ppp_profile ?? '');
        var profileUrl = @json(url('admin/services/sync/get_profiles'));
        var csrfToken = @json(csrf_token());

        $('#router').on('change', function () {
            var routerId = $(this).val();
            var mode = $('#mode').val();
            var profile = $('#profile');

            profile.empty().append($('<option>', {
                value: '',
                text: 'Memuat profile...'
            }));
            $('#pppprofile').fadeIn();

            if (!routerId || !mode) {
                profile.empty().append($('<option>', {
                    value: '',
                    text: 'Pilih router terlebih dahulu'
                }));
                return;
            }

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
                        profile.append($('<option>', {
                            value: '',
                            text: response.message || 'Gagal mengambil profile'
                        }));
                        return;
                    }

                    var rows = response.data || [];
                    if (!rows.length) {
                        profile.append($('<option>', {
                            value: '',
                            text: 'Tidak ada profile yang ditemukan'
                        }));
                        return;
                    }

                    profile.append($('<option>', {
                        value: '',
                        text: 'Pilih Profile'
                    }));

                    rows.forEach(function (item) {
                        if (!item.name) {
                            return;
                        }

                        profile.append($('<option>', {
                            value: item.name,
                            text: item.name,
                            selected: item.name === currentProfile
                        }));
                    });
                },
                error: function () {
                    profile.empty().append($('<option>', {
                        value: '',
                        text: 'Gagal mengambil data profile'
                    }));
                }
            });
        });
    });
</script>
@endsection
