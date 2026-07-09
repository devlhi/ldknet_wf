@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
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
                        <h4 class="mb-0">Coverage ODP</h4>

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
                    <div>
                        <button type="button" class="btn btn-success waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal"><i class="mdi mdi-plus me-1"></i> Tambah Data ODP </button>
                    </div>
                    <div class="row">
                    </div> <!-- end row-->
                    <div class="card">

                        <div class="card-body">
                            <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="float-left">
                                                <h6 class="modal-title" id="custom-width-modalLabel"><i class="fa fa-plus"></i> Tambah data ODP</h6>
                                            </div>
                                            <div class="float-right">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" action="{{ url('admin/coverage/odp/add') }}" role="form" method="POST">
                                                @csrf

                                                <div class="form-group">
                                                    <label class="col-md-2 control-label"> Nama ODP</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="nama" class="form-control" placeholder="Masukan Nama ODP">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">Jumlah Port</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="jumlah" class="form-control" placeholder="Masukan Jumlah Port ODP">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">Latitude</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="latitude" class="form-control" placeholder="Masukan Latitude ODP">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-md-2 control-label">Longitude</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="longitude" class="form-control" placeholder="Masukan Longitude ODP">
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                                    <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="fa fa-refresh"></i> Reset</button>
                                                    <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light" name="add"><i class="fa fa-plus"></i> Tambah</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div><!-- /.modal -->
                            <div class="card-body">
                                <div id="map" style="height: 500px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <div class="table-responsive">

                                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama ODP</th>
                                            <th>Jumlah Port ODP</th>
                                            <th>Port Digunakan</th>
                                            <th>Sisa Port Tersedia</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @php($i = 1)
                                        @foreach ($data as $row)
                                            <tr>
                                                <td>{{ $i++ }}</td>
                                                <td>{{ $row['nama'] }}</td>
                                                <td>{{ $row['total_port'] }} Port</td>
                                                <td>{{ $row['used_ports'] }} Port</td>
                                                <td>{{ implode(', ', $row['available_ports']) }}</td>
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

        <script>
            const apiKey = "AAPKc9c8af28d3f64383b6f04c6ac19a38fff2_tEPc1tZpDWVR0ORMAgTucm8B8fqCj5npJgB6v4vbf9uzWMist01edpkmCTc_K";
            const basemapEnum = "ArcGIS:Streets";

            const map = L.map("map", {
                maxZoom: 20,
            }).setView([-6.201543269940854, 106.922779489689], 14); //Latitude, longitude

            function getV2Basemap(style) {
                return L.esri.Vector.vectorBasemapLayer(style, {
                    apikey: apiKey,
                    version: 2
                })
            }

            const basemapLayers = {

                "osm/streets": getV2Basemap("osm/streets").addTo(map),
            };

            // Data ODP dikirim aman sebagai JSON; koordinat kosong/non-numerik
            // di-skip agar tidak merusak script, teks popup di-escape via textContent.
            var odpMarkers = @json($odp);
            odpMarkers.forEach(function (o) {
                var lat = parseFloat(o.latitude);
                var lng = parseFloat(o.longitude);
                if (isNaN(lat) || isNaN(lng)) {
                    return;
                }
                var popup = document.createElement('div');
                var judul = document.createElement('b');
                judul.textContent = 'Nama ODP : ' + (o.nama ?? '');
                popup.appendChild(judul);
                popup.appendChild(document.createElement('br'));
                popup.appendChild(document.createTextNode('Jumlah Port : ' + (o.port ?? '') + ' Port'));
                L.marker([lat, lng]).bindPopup(popup).addTo(map);
            });
        </script>
    </div>
@endsection
