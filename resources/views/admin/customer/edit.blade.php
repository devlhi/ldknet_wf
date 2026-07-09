@extends('admin.layout')

@section('content')
    @foreach ($datacustomer as $row)
        <div class="page-content">
            <div class="container-fluid">

                <div class="row mb-4">
                    <div class="col-xl-6">
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
                                <h4 class="card-title mb-4">Biodata Pelanggan</h4>
                                <form class="form-horizontal" action="{{ url('admin/customer/update/bio') }}" role="form" method="POST">
                                    @csrf
                                    <input type="hidden" name="idpel" value="{{ $row->idpel }}">
                                    <div class="row mb-3">
                                        <label class="col-sm-3 col-form-label">Nama</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="nama" value="{{ $row->nama }}" placeholder="Nama pelanggan" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-3 col-form-label">Email</label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" name="email" value="{{ $row->email }}" placeholder="Email pelanggan">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-3 col-form-label">No. HP</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="nomor" value="{{ $row->nomor }}" placeholder="Nomor HP / WhatsApp">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-sm-3 col-form-label">Alamat</label>
                                        <div class="col-sm-9">
                                            <textarea class="form-control" name="alamat" rows="2" placeholder="Alamat pelanggan">{{ $row->alamat }}</textarea>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Simpan Biodata</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Layanan &amp; Status</h4>
                                <form class="form-horizontal" action="{{ url('admin/customer/update') }}" role="form" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">ID Pelanggan </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" value="{{ $row->idpel }}" disabled>
                                                <input type="hidden" name="idpel" value="{{ $row->idpel }}">
                                                <input type="hidden" name="name" value="{{ $row->nama }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Layanan </label>
                                            <div class="col-sm-10">
                                                <select class="form-control select2" name="paket">
                                                    <option disabled value="" selected>{{ $row->paket }}</option>
                                                    @php $addedOptions = [$row->paket]; @endphp
                                                    @foreach ($getservice as $data)
                                                        @if ($data->paket != $row->paket && !in_array($data->paket, $addedOptions))
                                                            @php array_push($addedOptions, $data->paket); @endphp
                                                            <option>{{ $data->paket }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>

                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Tanggal Aktif </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" value="{{ tanggal($row->date) }}" disabled>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Masa Aktif Sampai </label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" value="{{ tanggal(date('Y-m-d', strtotime($row->expdate))) }}" disabled>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="username" class="col-sm-2 col-form-label">Status </label>
                                            <div class="col-sm-10">

                                                <select class="form-select" name="status">
                                                    <option>{{ $row->status }}</option>
                                                    <option value="Active">Active</option>
                                                    <option value="Berhenti">Berhenti</option>
                                                    <option value="Isolir">Isolir</option>
                                                </select>
                                            </div>
                                        </div>
                                        <a href="{{ url('admin/customers') }}" class="btn btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>

                                        <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Update Data Pelanggan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        @if (session('errors_pppoe'))
                            <div class="alert alert-danger alert-message" role="alert">
                                @foreach (session('errors_pppoe') as $err)
                                    {{ $err }}
                                @endforeach
                            </div>
                        @endif

                        @if (session('success_pppoe'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                                @foreach (session('success_pppoe') as $suc)
                                    {{ $suc }}
                                @endforeach
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                            </div>
                        @endif
                        <div class="card mb-0">
                            <!-- Nav tabs -->
                            <div class="card-body">
                                <h4 class="card-title mb-4">User</h4>
                                <form class="form-horizontal" action="{{ url('admin/customer/update/pppoe') }}" role="form" method="POST">
                                    @csrf
                                    <div class="card-body">
                                        <div class="example-container">
                                            <div class="example-content">
                                                <input type="hidden" value="{{ $row->idpel }}" name="idpel">
                                                <select class="form-control select2" name="user_pppoe" id="user_pppoe">
                                                    @if ($row->pppoe_user != null)
                                                        <option disabled value="" selected>{{ $row->pppoe_user }}</option>
                                                        @foreach ($pppoeuser as $data)
                                                            <option>{{ $data['name'] ?? '' }}</option>
                                                        @endforeach
                                                    @else
                                                        <option disabled value="" selected>Pilih Data User</option>

                                                        @foreach ($pppoeuser as $data)
                                                            <option>{{ $data['name'] ?? '' }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>

                                            </div>

                                        </div>
                                        <br>

                                        <button type="submit" class="btn btn-primary"><i class="uil uil-sync"></i> Update Data PPPOE</button>

                                    </div>

                                </form>

                            </div>
                        </div>
                        <br>
                        @if (session('errors_odp'))
                            <div class="alert alert-danger alert-message" role="alert">
                                @foreach (session('errors_odp') as $err)
                                    {{ $err }}
                                @endforeach
                            </div>
                        @endif

                        @if (session('success_odp'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                                @foreach (session('success_odp') as $suc)
                                    {{ $suc }}
                                @endforeach
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                            </div>
                        @endif
                        <div class="card mb-0">
                            <!-- Nav tabs -->
                            <div class="card-body">
                                <h4 class="card-title mb-4">ODP</h4>
                                <form class="form-horizontal" action="{{ url('admin/customer/update/odp') }}" role="form" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-lg-6">

                                            <input type="hidden" value="{{ $row->idpel }}" name="idpel">
                                            <div class="mb-3">
                                                <label class="form-label">Nama ODP</label>
                                                <select class="form-control select2" id="nama-odp-select" name="nama_odp">
                                                    @if ($row->nama_odp != null)
                                                        <option value="{{ $row->nama_odp }}" selected>{{ $row->nama_odp }}</option>


                                                        @foreach ($getodp as $dataodp)
                                                            <option value="{{ $dataodp->nama }}">{{ $dataodp->nama }}</option>
                                                        @endforeach
                                                    @else
                                                        <option value="" disabled selected>Pilih Data ODP</option>
                                                        @foreach ($getodp as $dataodp)
                                                            <option>{{ $dataodp->nama }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary"><i class="uil uil-sync"></i> Update Data ODP</button>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 mt-3 mt-lg-0">
                                                <label class="form-label">Port ODP</label>
                                                <select class="form-control select2" id="port-odp-select" name="port_odp">
                                                    <option value="" disabled selected>Pilih nama ODP terlebih dahulu</option>

                                                    <!-- Opsi akan ditambahkan secara dinamis di sini -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                            </div>

                        </div>

                    </div>

                </div>
                <!-- end row -->


            </div>
        </div>

        <!-- End Page-content -->
    @endforeach
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            function updatePortOptions(namaODP) {
                if (namaODP === "" || namaODP === null) {
                    // Nonaktifkan dropdown port jika nama ODP belum dipilih
                    $("#port-odp-select").prop("disabled", true);
                    $("#port-odp-select").empty().append($('<option>', {
                        value: "",
                        text: "Pilih nama ODP terlebih dahulu"
                    }));
                } else {
                    // Aktifkan dropdown port jika nama ODP dipilih
                    $("#port-odp-select").prop("disabled", false);

                    $.ajax({
                        url: '{{ url('/get-ports') }}',
                        method: 'POST',
                        data: {
                            namaODP: namaODP,
                            _token: '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        success: function(response) {
                            var odpPorts = response.jumlah_port;

                            // Mengambil data used ports beserta informasi idpel dari server
                            $.ajax({
                                url: '{{ url('/get-used-ports') }}',
                                method: 'GET',
                                dataType: 'json',
                                success: function(responseUsedPorts) {
                                    var usedPortsData = responseUsedPorts.data[namaODP] || [];

                                    $("#port-odp-select").empty();

                                    for (var i = 1; i <= response.jumlah_port; i++) {
                                        var optionText = i;
                                        var portInfo = usedPortsData[i.toString()];

                                        if (portInfo) {
                                            var idpelInfo = portInfo.idpel;
                                            optionText += ' ( Port Sudah digunakan Oleh : ' + idpelInfo + ' )';
                                        }

                                        $("#port-odp-select").append($('<option>', {
                                            value: i,
                                            text: optionText
                                        }));
                                    }
                                },
                                error: function() {
                                    console.log("Error: Failed to get used ports with idpel");
                                }
                            });
                        },
                        error: function() {
                            console.log("Error: Failed to get ports for ODP");
                        }
                    });

                }
            }

            $("#nama-odp-select").on("change", function() {
                var selectedNamaODP = $(this).val();
                updatePortOptions(selectedNamaODP);
            });

            // Inisialisasi saat halaman dimuat
            var defaultSelectedNamaODP = $("#nama-odp-select").val();
            updatePortOptions(defaultSelectedNamaODP);
        });
    </script>
@endsection
