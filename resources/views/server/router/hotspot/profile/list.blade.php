@extends('server.router.layout')

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
                        <h4 class="mb-0">Hotspot Profile</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">

                    <div class="card">
                        <div class="card-body">
                            @if (! ($dataLoaded ?? true))
                                <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span>Data Hotspot Profile belum dimuat dari RouterOS.</span>
                                    <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
                                </div>
                            @endif

                            <div class="col-lg-6">
                                <div class="example">
                                    <button type="button" class="btn btn-primary mb-1 mb-md-0" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-account-multiple-plus"></i> Add Profile</button>
                                </div>
                            </div>
                            <hr>

                            @if (session('auth_errors'))
                                <div class="alert alert-danger" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                                    <i class="fa fa-frown-o me-2" aria-hidden="true"></i>
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
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="float-left">
                                                <h6 class="modal-title" id="custom-width-modalLabel"><i class="mdi mdi-account-multiple-plus"></i> Add Profile</h6>
                                            </div>
                                            <div class="float-right">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" action="{{ url('server/router/hotspot/profile/add') }}" role="form" method="POST">
                                                @csrf

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label">Name</label>
                                                    <div class="col-md-12">
                                                        <input type="text" class="form-control" onchange="remSpace();" autocomplete="off" name="name" id="name">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label"> Rate limit [up/down]</label>
                                                    <div class="col-md-12">
                                                        <input class="form-control" type="text" autocomplete="off" name="ratelimit" id="ratelimit" placeholder="Example : 512k/1M">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label"> Masa Aktif [ Validity ]</label>
                                                    <div class="col-md-12">
                                                        <input class="form-control" type="text" autocomplete="off" name="uptime" id="uptime" placeholder="Example : 1h/4h/7h/30d">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label"> Harga</label>
                                                    <div class="col-md-12">
                                                        <input class="form-control" type="number" autocomplete="off" name="price" id="price" placeholder="Example : 1000">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label">Kunci Mac Address</label>
                                                    <div class="col-md-12">
                                                        <select class="form-select" aria-label="Default select example" name="mac" id="mac">
                                                            <option disabled value="" selected>Pilih salah satu</option>
                                                            <option value="Ya">Ya</option>
                                                            <option value="Tidak">Tidak</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-12 control-label"> Shared Users</label>
                                                    <div class="col-md-12">
                                                        <input class="form-control" type="text" autocomplete="off" name="shared" id="shared" placeholder="Example : 1">
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                    <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="mdi mdi-refresh"></i> Reset</button>
                                                    <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light"><i class="mdi mdi-account-multiple-plus"></i> Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div><!-- /.modal -->

                            <h6 class="card-title">Hotspot Profile</h6>

                            <div class="table-responsive">
                                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Name</th>
                                            <th>Shared Users</th>
                                            <th>Rate Limit</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($getprofile as $row)
                                            @php $id = str_replace('*', '', $row['.id']) @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['name'] }}</td>
                                                <td>{{ $row['shared-users'] }}</td>
                                                <td>{{ $row['rate-limit'] ?? '' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $id }}"><i class="mdi mdi-delete"></i> Delete Profile </button>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit-{{ $id }}"><i class="mdi mdi-grease-pencil"></i> Edit Profile </button>
                                                </td>
                                            </tr>
                                            <!--- Modal Delete -->
                                            <div class="modal fade" id="delete-{{ $id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Profile</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Apakah ingin menghapus profile <b><u>{{ $row['name'] }}</u></b> ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                                                            <button type="button" class="btn btn-primary" disabled title="Fitur hapus profile hotspot belum tersedia">Ya</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--- Modal Edit -->
                                            <div class="modal fade" id="edit-{{ $id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Profile</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form class="form-horizontal" action="{{ url('hotspot/edit_profile/' . $row['name']) }}" role="form" method="POST">
                                                                @csrf

                                                                <div class="form-group">
                                                                    <label class="col-md-12 control-label">Name</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" class="form-control" onchange="remSpace();" autocomplete="off" name="name" value="{{ $row['name'] }}">
                                                                    </div>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label class="col-md-12 control-label"> Rate limit [up/down]</label>
                                                                    <div class="col-md-12">
                                                                        <input class="form-control" type="text" autocomplete="off" name="ratelimit" value="{{ $row['rate-limit'] ?? '' }}">
                                                                    </div>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label class="col-md-12 control-label">Kunci Mac Address</label>
                                                                    <div class="col-md-12">
                                                                        <select class="form-select" aria-label="Default select example" name="mac">
                                                                            <option disabled value="" selected>Pilih salah satu</option>
                                                                            <option value="Ya">Ya</option>
                                                                            <option value="Tidak">Tidak</option>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label class="col-md-12 control-label"> Shared Users</label>
                                                                    <div class="col-md-12">
                                                                        <input class="form-control" type="text" autocomplete="off" name="shared" value="{{ $row['shared-users'] }}">
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                                    <button type="button" class="btn btn-success btn-bordered waves-effect w-md waves-light" disabled title="Fitur edit profile hotspot belum tersedia"><i class="mdi mdi-check-circle"></i> Submit</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                function remSpace() {
                    var upName = document.getElementsByName("name")[0];
                    var newUpName = upName.value.replace(/\s/g, "-");
                    upName.value = newUpName;
                    upName.focus();
                }
            </script>
        </div>
    </div>
@endsection
