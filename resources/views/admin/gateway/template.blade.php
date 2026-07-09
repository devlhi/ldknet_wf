@extends('admin.layout')

@section('content')
    @foreach ($content as $row)
        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Template Message</h4>
                                <p class="card-title-desc"> Semua kata dengan symbol <code>{}</code> tidak boleh dihapus maupun diganti.</p>

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

                                <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/template/update') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi tagihan dibuat </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_tagihan" class="form-control">{{ $row->notif_tagihan }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi pengingat tagihan </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_pengingat" class="form-control">{{ $row->notif_pengingat }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi tagihan dibuat ( Email ) </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_pembelian_voucher" class="form-control">{{ $row->notif_tagihan_email }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi pelanggan baru ( Email ) </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_pelanggan_baru_email" class="form-control">{{ $row->notif_pelanggan_baru_email }}</textarea>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi tagihan terbayar </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_tagihan_terbayar" class="form-control">{{ $row->notif_tagihan_terbayar }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi pelanggan baru </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_pelanggan_baru" class="form-control">{{ $row->notif_pelanggan_baru }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi pengingat tagihan ( Email ) </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_pembelian_voucher_terbayar" class="form-control">{{ $row->notif_pengingat_email }}</textarea>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Notifikasi tagihan terbayar ( Email ) </label>
                                                <div class="col-sm-12">
                                                    <textarea rows="15" name="notif_tagihan_terbayar_email" class="form-control">{{ $row->notif_tagihan_terbayar_email }}</textarea>
                                                </div>
                                            </div>

                                        </div>

                                        <div>
                                            <div>
                                                <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                                    Update
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div> <!-- end col -->
                </div>
            </div>
        </div>
    @endforeach
@endsection
