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
                        <h4 class="mb-0">Hotspot Host</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-body">

                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Mac Address</th>
                                        <th>Address</th>
                                        <th>To Address</th>
                                        <th>To Address</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($n = 0; $n < $totalhost; $n++)
                                        <tr>
                                            <td>{{ !empty($hosts[$n]['mac-address']) ? $hosts[$n]['mac-address'] : '' }}</td>
                                            <td>{{ !empty($hosts[$n]['address']) ? $hosts[$n]['address'] : '' }}</td>
                                            <td>{{ !empty($hosts[$n]['to-address']) ? $hosts[$n]['to-address'] : '' }}</td>
                                            <td>{{ !empty($hosts[$n]['server']) ? $hosts[$n]['server'] : '' }}</td>
                                            <td>{{ !empty($hosts[$n]['comment']) ? $hosts[$n]['comment'] : '' }}</td>
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
