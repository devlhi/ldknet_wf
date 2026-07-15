@extends('admin.layout')

@section('content')
@php
    $row = $payment->last();
@endphp
<div class="page-content">
    <div class="container-fluid">

        <div class="row">

            <div class="col-sm-6">

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
                <div class="card overflow-hidden">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fa fa-key"></i> Pengaturan Payment Gateway</h3>
                    </div>
                    <div class="card-body">

                        <div class="mb-3 row">
                            <label for="paymentgateway" class="col-sm-2 col-form-label">Payment Gateway</label>
                            <div class="col-sm-6">
                                <select class="form-select" aria-label="Default select example" name="gateway" id="gatewaySelect">
                                    @foreach ($defaultGateways as $data)
                                        <option value="{{ $data->name }}"> {{ $data->name }}</option>
                                    @endforeach

                                    @foreach ($otherGateways as $data)
                                        <option value="{{ $data->name }}"> {{ $data->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <button class="btn btn-info" id="setDefaultButton" style="display:none;">
                                    <i class="mdi mdi-check-circle-outline"></i> Jadikan Default
                                </button>
                            </div>
                        </div>

                        @if ($row !== null)
                        <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/gateway/payment/update') }}">
                            @csrf

                            @if ($row->name == 'tripay')
                                <input type="hidden" name="idnya" value="{{ $row->id }}">
                                <div class="mb-3 row">
                                    <label for="merchantcode" class="col-sm-2 col-form-label">Mode Sanbox</label>
                                    <div class="col-sm-10">
                                        <div class="square-switch">
                                            <input type="checkbox" name="sandboxMode" id="square-switch3" switch="bool" value="yes" {{ $row->sandbox === 'yes' ? 'checked' : '' }} />
                                            <label for="square-switch3" data-on-label="Yes" data-off-label="No"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="merchantcode" class="col-sm-2 col-form-label">Kode Merchant</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="code_merchant" name="code_merchant" value="{{ $row->code_merchant }}">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="apiurl" class="col-sm-2 col-form-label">API URL</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="api_url" name="api_url" value="{{ $row->api_url }}">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="apikey" class="col-sm-2 col-form-label">API Key</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="api_key" name="api_key" value="{{ $row->api_key }}">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="privatekey" class="col-sm-2 col-form-label">Private Key</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="private_key" name="private_key" value="{{ $row->private_key }}">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="status" class="col-sm-2 col-form-label">Status</label>
                                    <div class="col-sm-10">
                                        <select class="form-select" aria-label="Default select example" name="status">
                                            <option value="{{ $row->status }}"> {{ $row->status }}</option>
                                            <option value="enable">Enable</option>
                                            <option value="disable">Disable</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="buttonadd" class="col-sm-2 col-form-label"></label>
                                    <div class="col-sm-10">
                                        <button class="btn btn-primary" id="buttonadd" type="submit"><i class="fe fe-save"></i> Simpan</button>
                                    </div>
                                </div>
                            @elseif ($row->name == 'duitku')
                                <input type="hidden" name="idnya" value="{{ $row->id }}">
                                <div class="mb-3 row">
                                    <label for="merchantcode" class="col-sm-2 col-form-label">Mode Sanbox</label>
                                    <div class="col-sm-10">
                                        <div class="square-switch">
                                            <input type="checkbox" name="sandboxMode" id="square-switch3" switch="bool" value="yes" {{ $row->sandbox === 'yes' ? 'checked' : '' }} />
                                            <label for="square-switch3" data-on-label="Yes" data-off-label="No"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="merchantcode" class="col-sm-2 col-form-label">Kode Merchant</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="code_merchant" name="code_merchant" value="{{ $row->code_merchant }}" placeholder="DXXXX">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="apikey" class="col-sm-2 col-form-label">API Key</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="api_key" name="api_key" value="{{ $row->api_key }}" placeholder="Merchant Key Duitku">
                                        <small class="text-muted">Merchant Key dari dashboard Duitku (dipakai untuk signature).</small>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="status" class="col-sm-2 col-form-label">Status</label>
                                    <div class="col-sm-10">
                                        <select class="form-select" aria-label="Default select example" name="status">
                                            <option value="{{ $row->status }}"> {{ $row->status }}</option>
                                            <option value="enable">Enable</option>
                                            <option value="disable">Disable</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="buttonadd" class="col-sm-2 col-form-label"></label>
                                    <div class="col-sm-10">
                                        <button class="btn btn-primary" id="buttonadd" type="submit"><i class="fe fe-save"></i> Simpan</button>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="idnya" value="{{ $row->id }}">

                                <div class="mb-3 row">
                                    <label for="apiurl" class="col-sm-2 col-form-label">API URL</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="api_url" name="api_url" value="{{ $row->api_url }}">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="apikey" class="col-sm-2 col-form-label">API Key</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="api_key" name="api_key" value="{{ $row->api_key }}">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="status" class="col-sm-2 col-form-label">Status</label>
                                    <div class="col-sm-10">
                                        <select class="form-select" aria-label="Default select example" name="status">
                                            <option value="{{ $row->status }}"> {{ $row->status }}</option>
                                            <option value="enable">Enable</option>
                                            <option value="disable">Disable</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="buttonadd" class="col-sm-2 col-form-label"></label>
                                    <div class="col-sm-10">
                                        <button class="btn btn-primary" id="buttonadd" type="submit"><i class="fe fe-save"></i> Simpan</button>
                                    </div>
                                </div>
                            @endif
                        </form>
                        @endif

                    </div>

                </div>
            </div>

            <!-- COL END -->
            <div class="col-sm-6">

                @if (session('errors_pm'))
                    <div class="alert alert-danger alert-message" role="alert">
                        @foreach (session('errors_pm') as $err)
                            {{ $err }}
                        @endforeach
                    </div>
                @endif

                @if (session('success_pm'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                        @foreach (session('success_pm') as $suc)
                            {{ $suc }}
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                    </div>
                @endif
                <div class="card overflow-hidden">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fa fa-credit-card"></i> Payment Method</h3>
                        <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm"><i class="uil uil-eye me-1"></i> Tampilkan Data</a>
                    </div>
                    <div class="card-body">
                        @if (! $showData)
                            <p class="text-center text-muted mb-0">Klik Tampilkan Data untuk memuat data.</p>
                        @elseif ($method->isEmpty())
                            <p class="text-center text-muted mb-0">Belum ada payment method untuk provider aktif.</p>
                        @endif
                        <div class="form-group">
                            @foreach ($method as $row)
                                <div class="custom-control custom-switch d-block">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input form-control-md" id="set{{ $row->provider_code }}" {{ $row->status == '1' ? 'checked' : '' }}>
                                        <span class="settingsIntegrationOneSwitcher"></span>
                                        <span class="form-check-label">{{ $row->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });
</script>
@foreach ($method as $row)
    <script>
        $('#set{{ $row->provider_code }}').on('change', function(event) {
            var status = $('#set{{ $row->provider_code }}').is(":checked") ? 1 : 0;
            var kodepm = '{{ $row->provider_code }}';
            $.ajax({
                url: "{{ url('admin/gateway/payment/method/update') }}",
                method: "POST",
                data: {
                    status: status,
                    kodepm: kodepm
                },
                async: true,
                dataType: 'html',
                success: function() {
                    location.reload();
                }
            });
        });
    </script>
@endforeach
<script>
    $(document).ready(function() {
        $('#gatewaySelect').on('change', function() {
            var selectedGateway = $(this).val();
            var setDefaultButton = $('#setDefaultButton');
            setDefaultButton.hide(); // Sembunyikan tombol secara default

            @foreach ($otherGateways as $data)
                @if ($data->payment_default == '0')
                    if (selectedGateway == '{{ $data->name }}') {
                        setDefaultButton.show(); // Tampilkan tombol jika gateway belum menjadi default
                    }
                @endif
            @endforeach
        });

        $('#setDefaultButton').on('click', function() {
            var selectedGateway = $('#gatewaySelect').val();

            // Kirim permintaan AJAX untuk mengubah payment_default
            $.ajax({
                url: '{{ url('admin/gateway/payment/set-default') }}',
                method: 'POST',
                data: {
                    gatewayName: selectedGateway
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        });
    });
</script>
@endsection
