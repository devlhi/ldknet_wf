@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
    <!-- Load Leaflet from CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha384-sHL9NAb7lN7rfvG5lfHpm643Xkcjzp4jFvuavGOndn6pjVqS6ny56CAt3nsEVT4H" crossorigin="anonymous" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha384-cxOPjt7s7Iz04uaHJceBmS+qpjv2JkIHNVcuOrM+YHwZOmJGBXI00mdUXEq65HTH" crossorigin="anonymous"></script>
    <!-- Load Esri Leaflet from CDN -->
    <script src="https://unpkg.com/esri-leaflet@3.0.10/dist/esri-leaflet.js" integrity="sha384-XFv+t06VIQWRTfSHZMk+rBg6p+lWJoBz5l/c7iVOWUnsy4G2tRCE0545uRHXZhpk" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/esri-leaflet-vector@4.1.0/dist/esri-leaflet-vector.js" integrity="sha384-DxZMKZVCV0WBC/5WVw07KHUM6lsxwY74JbfxIggfVnsXherK8TyuHxtn3jGN9Pxy" crossorigin="anonymous"></script>
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
                        <h4 class="mb-0">Get Coordinat Location</h4>

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

                    <div class="row">
                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">

                            <div class="card-body">

                                <div class="form-group">
                                    <label for="">Latitude</label>
                                    <input type="text" id="Latitude" name="Latitude" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="">Longitude</label>
                                    <input type="text" id="Longitude" name="Longitude" class="form-control">

                                </div>

                                <br>
                                <div id="map" style="height: 500px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="https://code.jquery.com/jquery-1.10.2.js" integrity="sha384-r0tJvB87edk25TJle8mfwmdYBwaGtkX3r4CYHXS+2yZ7VPdI8xd2rHl6KTQ6oij4" crossorigin="anonymous"></script>

        <script>
            var curLocation = [0, 0];
            if (curLocation[0] == 0 && curLocation[1] == 0) {
                curLocation = [-6.201543269940854, 106.922779489689];
            }

            const apiKey = "AAPKc9c8af28d3f64383b6f04c6ac19a38fff2_tEPc1tZpDWVR0ORMAgTucm8B8fqCj5npJgB6v4vbf9uzWMist01edpkmCTc_K";

            const map = L.map("map", {
                maxZoom: 20,
            }).setView([-6.201543269940854, 106.922779489689], 15); //Garis Lintang, Garis Bujur

            function getV2Basemap(style) {
                return L.esri.Vector.vectorBasemapLayer(style, {
                    apikey: apiKey,
                    version: 2
                });
            }

            const basemapLayers = {

                "arcgis/outdoor": getV2Basemap("arcgis/outdoor").addTo(map),

                "arcgis/community": getV2Basemap("arcgis/community"),
                "arcgis/navigation": getV2Basemap("arcgis/navigation"),
                "arcgis/streets": getV2Basemap("arcgis/streets"),
                "arcgis/streets-relief": getV2Basemap("arcgis/streets-relief"),
                "arcgis/imagery": getV2Basemap("arcgis/imagery"),
                "arcgis/oceans": getV2Basemap("arcgis/oceans"),
                "arcgis/topographic": getV2Basemap("arcgis/topographic"),
                "arcgis/light-gray": getV2Basemap("arcgis/light-gray"),
                "arcgis/dark-gray": getV2Basemap("arcgis/dark-gray"),
                "arcgis/human-geography": getV2Basemap("arcgis/human-geography"),
                "arcgis/charted-territory": getV2Basemap("arcgis/charted-territory"),
                "arcgis/nova": getV2Basemap("arcgis/nova"),
                "osm/standard": getV2Basemap("osm/standard"),
                "osm/navigation": getV2Basemap("osm/navigation"),
                "osm/streets": getV2Basemap("osm/streets"),
                "osm/blueprint": getV2Basemap("osm/blueprint")
            };

            L.control.layers(basemapLayers, null, {
                collapsed: false
            }).addTo(map);



            map.attributionControl.setPrefix(false);

            var marker = new L.marker(curLocation, {
                draggable: true // 'true' tidak perlu diapit dengan tanda kutip
            });

            marker.on('dragend', function(event) {
                var position = marker.getLatLng();
                marker.setLatLng(position, {
                    draggable: true // 'true' tidak perlu diapit dengan tanda kutip
                }).bindPopup(position.toString()).update();
                $("#Latitude").val(position.lat);
                $("#Longitude").val(position.lng).keyup();
            });

            $("#Latitude, #Longitude").change(function() {
                var position = [parseFloat($("#Latitude").val()), parseFloat($("#Longitude").val())];
                marker.setLatLng(position, {
                    draggable: true // 'true' tidak perlu diapit dengan tanda kutip
                }).bindPopup(position.toString()).update();
                map.panTo(position);
            });
            map.addLayer(marker);
        </script>
    </div>
@endsection
