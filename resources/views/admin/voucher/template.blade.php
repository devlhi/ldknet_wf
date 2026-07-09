@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Template Whatsapp Message</h4>
                            <p class="card-title-desc">Semua kata dengan symbol <code>{}</code> tidak boleh dihapus maupun diganti.</p>

                            @if (session('auth_errors'))
                                <div class="alert alert-danger alert-message" role="alert">
                                    @foreach ((array) session('auth_errors') as $err)
                                        {{ $err }}
                                    @endforeach
                                </div>
                            @endif

                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                                    @foreach ((array) session('success') as $suc)
                                        {{ $suc }}
                                    @endforeach
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                                </div>
                            @endif

                            @forelse ($content as $row)
                            <form autocomplete="off" name="formadd" method="post" action="{{ url('server/voucher/update/template') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $row->id }}">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi Pembelian</label>
                                            <div class="col-sm-12">
                                                <textarea rows="15" name="notif_pembelian" class="form-control">{{ $row->notif_pembelian }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi Pembayaran</label>
                                            <div class="col-sm-12">
                                                <textarea rows="15" name="notif_pembayaran" class="form-control">{{ $row->notif_pembayaran }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1">Update</button>
                                    </div>
                                </div>
                            </form>
                            @empty
                                <div class="alert alert-warning mb-0" role="alert">
                                    Belum ada template pesan voucher. Impor tabel template dari instalasi lama untuk mengelola template di sini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
