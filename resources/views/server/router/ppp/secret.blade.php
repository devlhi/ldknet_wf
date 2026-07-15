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
                        <h4 class="mb-0">PPP Secret</h4>
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
                                        <span>Data PPP Secret belum dimuat dari RouterOS.</span>
                                        <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
                                    </div>
                                @endif

                                <!-- sample modal content -->
                                <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div class="float-left">
                                                    <h6 class="modal-title" id="custom-width-modalLabel"><i class="fa fa-plus"></i> Add Secret</h6>
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
                                                            <input type="text" name="name" class="form-control" autocomplete="off" value="">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-2 control-label"> Password</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="password" class="form-control" autocomplete="off" value="">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-2 col-form-label">Profile </label>
                                                        <div class="col-md-12">
                                                            <select class="form-select" aria-label="Default select example" name="profile" id="profile">
                                                                <option disabled value="" selected>Pilih salah satu</option>
                                                                @foreach ($profile as $data)
                                                                    <option>{{ $data['name'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-2 col-form-label">Service</label>
                                                        <div class="col-md-12">
                                                            <select class="form-select" aria-label="Default select example" name="service" id="service">
                                                                <option disabled value="" selected>Pilih salah satu</option>
                                                                <option value="any">any</option>
                                                                <option value="async">async</option>
                                                                <option value="l2tp">l2tp</option>
                                                                <option value="ovpn">ovpn</option>
                                                                <option value="pppoe">pppoe</option>
                                                                <option value="pptp">pptp</option>
                                                                <option value="sstp">sstp</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="col-md-2 control-label"> Remote Address</label>
                                                        <div class="col-md-12">
                                                            <input type="text" name="remoteaddr" class="form-control" autocomplete="off" value="">
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                        <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="fa fa-refresh"></i> Reset</button>
                                                        <button type="button" class="btn btn-success btn-bordered waves-effect w-md waves-light" disabled title="Fitur tambah secret PPP belum tersedia"><i class="fa fa-plus"></i> Tambah</button>
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
                                                    Add Secret
                                                </button>
                                            </div>
                                        </div>
                                        <br>

                                        <div class="table-responsive">
                                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Nama</th>
                                                        <th>Local Address</th>
                                                        <th>Remote Address</th>
                                                        <th>Profile</th>
                                                        <th>Service</th>
                                                        <th>Last Logged Out</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($getsecret as $row)
                                                        @php $id = str_replace('*', '', $row['.id']) @endphp
                                                        <tr>
                                                            <td>{{ $row['name'] }}</td>
                                                            <td>{{ !empty($row['local-address']) ? $row['local-address'] : '' }}</td>
                                                            <td>{{ !empty($row['remote-address']) ? $row['remote-address'] : '' }}</td>
                                                            <td>{{ $row['profile'] }}</td>
                                                            <td>{{ $row['service'] }}</td>
                                                            <td>{{ !empty($row['last-logged-out']) ? $row['last-logged-out'] : '' }}</td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $id }}"><i class="uil-trash"></i> Hapus Data </button>

                                                                <button type="button" class="btn btn-sm btn-warning" disabled title="Fitur edit secret PPP belum tersedia"><i class="uil-edit"></i> Edit</button>
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
                                                                        Apakah anda ingin menghapus secret <b><u>{{ $row['name'] }}</u></b> ?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                                                                        <button type="button" class="btn btn-primary" disabled title="Fitur hapus secret PPP belum tersedia">Ya</button>
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
@endsection
