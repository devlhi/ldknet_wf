@extends('server.olt.layout')

@section('content')
<!-- ============================================================== -->
<!-- Start right Content here -->
<!-- ============================================================== -->

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-12 col-lg-7">
                <div class="card shadow mb-4">
                    <!-- Card Body -->
                    <div class="card-body">

                        @if (! ($dataLoaded ?? true))
                            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span>Data ONU belum dimuat dari OLT.</span>
                                <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
                            </div>
                        @endif

                        <div class="col-lg-12">
                            <div class="row">
                                <div class="table-responsive">
                                    <table id="datatable" class="table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>ONU ID</th>
                                                <th>Nama</th>
                                                <th>MAC </th>
                                                <th>Status</th>
                                                <th>Last Deregister Time</th>
                                                <th>Last Deregister Reason</th>
                                                <th>Receiver Power</th>
                                                <th>#</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($onu as $row)
                                                <tr>
                                                    <td>{{ $row['port_id'] }}/{{ $row['onu_id'] }}</td>
                                                    <td>{{ $row['onu_name'] }}</td>
                                                    <td>{{ $row['macaddr'] }}</td>
                                                    @php
                                                        if ($row['status'] == 'Online') {
                                                            $label = 'success';
                                                        } else {
                                                            $label = 'danger';
                                                        }
                                                    @endphp

                                                    <td><span class="btn btn-sm btn-{{ $label }}">{{ $row['status'] }}</span></td>

                                                    <td>{{ $row['last_down_time'] }}</td>
                                                    <td>{{ $row['last_down_reason'] }}</td>
                                                    @php
                                                        if ($row['receive_power'] > -8) {
                                                            $labelnya = 'danger';
                                                        } elseif ($row['receive_power'] < -8 && $row['receive_power'] > -27) {
                                                            $labelnya = 'success';
                                                        } else {
                                                            $labelnya = 'danger';
                                                        }
                                                    @endphp
                                                    <td><span class="btn btn-sm btn-{{ $labelnya }}">{{ $row['receive_power'] }}</span></td>
                                                    <td>
                                                        <a href="{{ url('server/olt/detail/port/' . $row['port_id'] . '/onu/' . $row['onu_id']) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-arrow-right"></i> Detail Onu</a>
                                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row['onu_id'] }}"><i class="uil uil-trash"></i> Delete</button>

                                                        <div class="modal fade" id="delete-{{ $row['onu_id'] }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Data</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Apakah anda ingin menghapus Onu <b><u>{{ $row['onu_name'] }}</u></b> ?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <a class="btn btn-primary" href="{{ url('server/olt/onu/delete/port/' . $row['port_id'] . '/onu/' . $row['onu_id'] . '/mac/' . $row['macaddr']) }}">Yes</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
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
@endsection
