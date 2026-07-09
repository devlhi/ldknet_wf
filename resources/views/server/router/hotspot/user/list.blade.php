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
                        <h4 class="mb-0">Hotspot Users</h4>
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

                    <div class="card">

                        <div class="card-body">

                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>{{ $totalhotspotuser }}</th>
                                        <th>Server</th>
                                        <th>Name</th>
                                        <th>Profile </th>
                                        <th>Mac Address</th>
                                        <th>Uptime</th>
                                        <th>Bytes In</th>
                                        <th>Bytes Out</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hotspotuser as $row)
                                        @php $id = str_replace('*', '', $row['.id']) @endphp
                                        <tr>
                                            <td><button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row['name'] }}"><i class="uil uil-trash"></i>Delete</button>
                                            </td>

                                            <td>{{ !empty($row['server']) ? $row['server'] : '' }}</td>

                                            <td>{{ $row['name'] }}</td>

                                            <td>{{ !empty($row['profile']) ? $row['profile'] : '' }}</td>
                                            <td>{{ !empty($row['mac-address']) ? $row['mac-address'] : '' }}</td>
                                            <td>{{ formatDTM($row['uptime']) }}</td>
                                            <td>{{ formatBytes($row['bytes-in']) }}</td>
                                            <td>{{ formatBytes($row['bytes-out']) }}</td>
                                            <td>{{ !empty($row['comment']) ? $row['comment'] : '' }}</td>

                                            <!--- Modal Delete -->
                                            <div class="modal fade" id="delete-{{ $row['name'] }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete User</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Apakah anda ingin menghapus user <b><u>{{ $row['name'] }}</u></b> ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <a class="btn btn-primary" href="{{ url('server/router/hotspot/user/delete/' . $id) }}">Yes</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div>
    </div>
@endsection
