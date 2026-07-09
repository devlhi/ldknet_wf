@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        @foreach ($content as $row)
            <div class="row">
                <div class="col-md-6">
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
                            <h3 class="card-title"><i class="uil uil-whatsapp"></i> Whatsapp Gateway</h3>
                        </div>
                        <div class="card-body">
                            <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/whatsapp/update/number') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $row->id }}">

                                <div class="mb-3">
                                    <label for="merchantcode" class="col-sm-2 col-form-label">Mode</label>
                                    <div class="square-switch">
                                        <input type="checkbox" name="mode" id="square-switch1" switch="none" value="on" {{ $row->mode === 'on' ? 'checked' : '' }} />
                                        <label for="square-switch1" data-on-label="On" data-off-label="Off"></label>
                                    </div>
                                </div>

                                <div id="formContainer" style="display:{{ $row->mode === 'on' ? 'block' : 'none' }};">

                                    <div class="mb-3">
                                        <label for="apikey" class="form-label">API Url Whatsapp Gateway</label>
                                        <input type="text" class="form-control" name="api_url" value="{{ $row->api_url }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="apikey" class="form-label">API Key Whatsapp Gateway</label>
                                        <input type="text" class="form-control" name="api_key" value="{{ $row->api_key }}">
                                    </div>

                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                        Update
                                    </button>
                                </div>

                            </form>

                        </div>

                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card overflow-hidden">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title"><i class="uil uil-bullhorn"></i> Informasi</h3>
                        </div>
                        <div class="card-body">
                            <pre>Mohon ikuti arahan Admin</pre>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modeSwitch = document.getElementById('square-switch1');
    const formContainer = document.getElementById('formContainer');

    modeSwitch.addEventListener('change', function() {
        if (modeSwitch.checked) {
            formContainer.style.display = 'block';
        } else {
            formContainer.style.display = 'none';
        }
    });

    // Initial state
    if (!modeSwitch.checked) {
        formContainer.style.display = 'none';
    }
</script>
@endsection
