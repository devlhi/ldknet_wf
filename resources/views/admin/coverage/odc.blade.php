@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
@endsection

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
                        <h4 class="mb-0">Coverage ODC</h4>

                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row">
                <div class="col-12">

                    <div class="row">
                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">
                            <div id="map" style="height: 500px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var map = L.map('map').setView([-6.205850380571469, 106.92191621173872], 15);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var odc1 = L.marker([-6.197478592210463, 106.92223551910205]).bindPopup("<b>ODC 1</b>").addTo(map);
        var odc2 = L.marker([-6.199259322567293, 106.92072053629074]).bindPopup("<b>ODC 2</b>").addTo(map);
        var odc3 = L.marker([-6.200011939096899, 106.92243479279152]).bindPopup("<b>ODC 3</b>").addTo(map);
        var odc4 = L.marker([-6.198610335224289, 106.92226254888386]).bindPopup("<b>ODC 4</b>").addTo(map);
    </script>
@endsection
