@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
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
                        <h3 class="card-title"><i class="uil uil-whatsapp"></i> Text Message</h3>
                    </div>
                    <div class="card-body">
                        @if ($content->contains(fn ($gateway) => \App\Support\WhatsAppGatewayResolver::isMeta($gateway)))
                            <div class="alert alert-info small">
                                Meta hanya menerima pesan teks bebas dalam 24 jam setelah pelanggan mengirim pesan. Untuk pesan pertama atau percakapan yang sudah lewat 24 jam, gunakan <a href="{{ url('admin/whatsapp/meta/templates') }}" class="alert-link">Template APPROVED</a>.
                            </div>
                        @endif
                        <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/whatsapp/message/text-message/send') }}">
                            @csrf

                            <div class="mb-3 row">
                                <label for="apikey" class="col-sm-2 col-form-label">Penerima</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="received" name="received" placeholder="Masukan nomor whatsapp penerima">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="apikey" class="col-sm-2 col-form-label">Pesan</label>
                                <div class="col-sm-10">
                                    <textarea rows="5" name="message" class="form-control"></textarea>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="buttonadd" class="col-sm-2 col-form-label"></label>
                                <div class="col-sm-10">
                                    <button class="btn btn-primary" id="buttonadd" type="submit"><i class="uil uil-message"></i> Send Message</button>
                                </div>
                            </div>

                        </form>

                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
