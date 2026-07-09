@extends('server.olt.layout')

@section('content')
<!-- ============================================================== -->
<!-- Start right Content here -->
<!-- ============================================================== -->

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        @if (session('auth_errors'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                                @foreach (session('auth_errors') as $err)
                                    {{ $err }}
                                @endforeach
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
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

                        <h5 class="card-title">ONU INFO </h5>
                        <table class="table table-bordered">
                            <tr>
                                <td scope="col"><b>Name</b></td>
                                <td scope="col">{{ $base->onu_name }}</td>
                            </tr>
                            <tr>
                                <td scope="col"><b>Mac Address</b></td>
                                <td scope="col">{{ $base->macaddr }}</td>
                            </tr>
                            <tr>
                                <td scope="col"><b>Distance</b></td>
                                <td scope="col">{{ $base->distance }}M</td>
                            </tr>
                            <tr>
                                <td scope="col"><b>Vendor</b></td>
                                <td scope="col">{{ $base->vendor }}</td>
                            </tr>
                            <tr>
                                <td scope="col"><b>Model</b></td>
                                <td scope="col">{{ $base->extmodel }}</td>
                            </tr>
                        </table>
                        <hr>
                        <a href="{{ url('server/olt/pon/' . $base->port_id) }}" class="btn btn-success m-b-10"><i class="mdi mdi-arrow-left"></i>
                            Kembali</a>

                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="">
                            <div class="">
                                <h5 class="card-title">ONU Optical Diagnose</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <td scope="col"><b>Temperature</b></td>
                                        <td scope="col">{{ $dataoptic->work_temprature }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="col"><b>Voltage</b></td>
                                        <td scope="col">{{ $dataoptic->work_voltage }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="col"><b>Transmit Power</b></td>
                                        <td scope="col">{{ $dataoptic->transmit_power }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="col"><b>Receive Power</b></td>
                                        <td scope="col">{{ $dataoptic->receive_power }}</td>
                                    </tr>
                                </table>
                                <hr>
                                @if (auth()->user()->level == 'developer')
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit-{{ $dataoptic->onu_id }}">
                                        <i class="mdi mdi-pencil-box-outline"></i>
                                        Ganti Nama ONU
                                    </button>

                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reboot-{{ $dataoptic->onu_id }}">
                                        <i class="mdi mdi-power"></i>
                                        Reboot ONU
                                    </button>

                                    <div class="modal fade" id="reboot-{{ $dataoptic->onu_id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reboot Onu</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah anda yakin ingin merestart onu <b><u>{{ $base->onu_name }}</u></b> ?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <a class="btn btn-primary" href="{{ url('server/olt/onu/reboot/port/' . $dataoptic->port_id . '/onu/' . $dataoptic->onu_id) }}">Yes</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="edit-{{ $dataoptic->onu_id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ganti Nama ONU</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form class="form-horizontal" action="{{ url('server/olt/change/name/port/' . $dataoptic->port_id . '/onu/' . $dataoptic->onu_id) }}" role="form" method="POST">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label">Nama Sebelumnya</label>
                                                            <div class="col-md-12">
                                                                <input type="text" class="form-control" value="{{ $base->onu_name }}" readonly>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="col-md-5 control-label">Nama Baru</label>
                                                            <div class="col-md-12">
                                                                <input type="text" class="form-control" name="nama" placeholder="Masukan Nama Baru">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-info"> <i class="mdi mdi-send"></i>Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
