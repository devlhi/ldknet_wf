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
                        <h4 class="mb-0">PPP Profile</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <!-- end row -->
                <div class="row">
                    <div class="col-xl-12 col-lg-7">
                        <div class="card shadow mb-4">
                            <!-- Card Body -->
                            <div class="card-body">
                                @if (! ($dataLoaded ?? true))
                                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span>Data PPP Profile belum dimuat dari RouterOS.</span>
                                        <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
                                    </div>
                                @endif

                                <!-- sample modal content -->
                                <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div class="float-left">
                                                    <h6 class="modal-title" id="custom-width-modalLabel"><i class="fa fa-plus"></i> Add Profile</h6>
                                                </div>
                                                <div class="float-right">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <form class="form-horizontal" action="#" role="form" method="POST" onsubmit="return false;">
                                                    @csrf

                                                    <div class="form-group">
                                                        <label class="col-md-12 control-label">Name</label>
                                                        <div class="col-md-12">
                                                            <input type="text" class="form-control" onchange="remSpace();" autocomplete="off" name="name" value="">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-12 control-label"> Local address</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="localaddr" class="form-control" autocomplete="off" placeholder="192.168.1.1">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-12 control-label">Remote Address</label>
                                                        <div class="col-md-12">
                                                            <select class="form-select" aria-label="Default select example" name="remoteaddr">
                                                                <option>None</option>
                                                                @foreach ($pool as $data)
                                                                    <option>{{ $data['name'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="col-md-12 control-label"> Rate limit [up/down]</label>
                                                        <div class="col-md-12">
                                                            <input class="form-control" type="text" name="ratelimit" autocomplete="off" placeholder="Example : 512k/1M">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-12 control-label">Only One</label>
                                                        <div class="col-md-12">
                                                            <select class="form-control" name="onlyone">
                                                                <option value="yes">Yes</option>
                                                                <option value="no">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-12 control-label">Parent Queue</label>
                                                        <div class="col-md-12">
                                                            <select class="form-control" name="parent">
                                                                <option value="none">None</option>
                                                                @foreach ($queue as $data)
                                                                    <option>{{ $data['name'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                        <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="fa fa-refresh"></i> Reset</button>
                                                        <button type="button" class="btn btn-success btn-bordered waves-effect w-md waves-light" disabled title="Fitur tambah profile PPP belum tersedia"><i class="fa fa-plus"></i> Tambah</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div><!-- /.modal -->
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="input-group mb-3">
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#myModal">
                                                    Add Profile
                                                </button>
                                            </div>
                                        </div>
                                        <br>

                                        <div class="table-responsive">
                                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Nama</th>
                                                        <th>Local Address </th>
                                                        <th>Remote Address</th>
                                                        <th>Ratelimit</th>
                                                        <th>Parent Queue</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($getprofile as $row)
                                                        @php $id = str_replace('*', '', $row['.id']) @endphp
                                                        <tr>
                                                            <td>{{ $row['name'] }}</td>
                                                            <td>{{ !empty($row['local-address']) ? $row['local-address'] : '' }}</td>
                                                            <td>{{ !empty($row['remote-address']) ? $row['remote-address'] : '' }}</td>
                                                            <td>{{ !empty($row['rate-limit']) ? $row['rate-limit'] : '' }}</td>
                                                            <td>{{ !empty($row['parent-queue']) ? $row['parent-queue'] : '' }}</td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $id }}"><i class="uil-trash"></i> Hapus Profile </button>
                                                                <button type="button" class="btn btn-sm btn-warning" disabled title="Fitur edit profile PPP belum tersedia"><i class='uil-edit'></i> Edit</button>
                                                            </td>
                                                        </tr>
                                                        <div class="modal fade" id="delete-{{ $id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Hapus Profile</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Apakah anda ingin menghapus Profile <b><u>{{ $row['name'] }}</u></b> ?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                                                                        <button type="button" class="btn btn-primary" disabled title="Fitur hapus profile PPP belum tersedia">Ya</button>
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
@endsection
