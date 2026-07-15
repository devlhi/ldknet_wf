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
                        <h4 class="mb-0">Hotspot Log</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-body">

                            @if (! ($dataLoaded ?? true))
                                <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span>Log Hotspot belum dimuat dari RouterOS.</span>
                                    <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
                                </div>
                            @endif

                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Time</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($n = 0; $n < $totalhotspotlog; $n++)
                                        <tr>
                                            <td>{{ $n + 1 }}</td>
                                            <td>{{ $log[$n]['time'] }}</td>
                                            <td>{{ $log[$n]['message'] }}</td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div>
    </div>
@endsection
