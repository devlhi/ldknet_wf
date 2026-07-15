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
                        <h4 class="mb-0">PPP Active</h4>
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
                                        <span>Data PPP Active belum dimuat dari RouterOS.</span>
                                        <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
                                    </div>
                                @endif

                                <div class="col-lg-12">
                                    <div class="row">

                                        <br>

                                        <div class="table-responsive">
                                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Nama</th>
                                                        <th>Service</th>
                                                        <th>Caller ID</th>
                                                        <th>Address</th>
                                                        <th>Uptime</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($getsecret as $row)
                                                        <tr>
                                                            <td>{{ $row['name'] }}</td>
                                                            <td>{{ $row['service'] }}</td>
                                                            <td>{{ $row['caller-id'] ?? '' }}</td>
                                                            <td>{{ $row['address'] ?? '' }}</td>
                                                            <td>{{ formatDTM($row['uptime']) }}</td>
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
    </div>
@endsection
