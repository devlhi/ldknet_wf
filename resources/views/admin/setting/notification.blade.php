@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        @foreach ($content as $row)
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
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Setting Notification</h5>
                        </div>


                        <div class="card-body">
                            <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/setting/notification/update') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $row->id }}">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi Sebelum Jatuh Tempo
                                                <span class="text-primary cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right" title="Notifikasi sebelum jatuh tempo adalah invoice akan dibuat, contoh : tanggal masa aktif sampai tanggal 5, jika anda mengisikan form tersebut angka 5 , maka invoice akan otomatis dibuat pada tanggal 30 / 31 ">[?]</span>
                                            </label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="text" class="form-control" id="sebelum" name="sebelum" value="{{ $row->sebelum }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi saat tagihan pelanggan dibuat</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_tagihan" id="square-switch1" switch="none" value="on" {{ $row->notif_tagihan === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch1" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi tagihan pada tanggal jatuh tempo</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_jatuh_tempo_h" id="square-switch2" switch="none" value="on" {{ $row->notif_jatuh_tempo_h === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch2" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi tagihan 1 hari sebelum jatuh tempo</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_jatuh_tempo_h1" id="square-switch3" switch="none" value="on" {{ $row->notif_jatuh_tempo_h1 === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch3" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi tagihan 3 hari sebelum jatuh tempo</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_jatuh_tempo_h3" id="square-switch4" switch="none" value="on" {{ $row->notif_jatuh_tempo_h3 === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch4" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi tagihan 7 hari sebelum jatuh tempo</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_jatuh_tempo_h7" id="square-switch5" switch="none" value="on" {{ $row->notif_jatuh_tempo_h7 === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch5" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi saat pelanggan baru dibuat</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_pelanggan_baru" id="square-switch6" switch="none" value="on" {{ $row->notif_pelanggan_baru === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch6" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi tagihan telah terbayar</label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_tagihan_terbayar" id="square-switch7" switch="none" value="on" {{ $row->notif_tagihan_terbayar === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch7" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi pembelian voucher
                                                <span class="text-primary cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right" title="Aktif jika whatsapp gateway menyala">[?]</span>

                                            </label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_pembelian_voucher" id="square-switch8" switch="none" value="on" {{ $row->notif_pembelian_voucher === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch8" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notifikasi pembelian voucher telah terbayar
                                                <span class="text-primary cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right" title="Aktif jika whatsapp gateway menyala">[?]</span>

                                            </label>
                                            <div class="col-sm-5">
                                                <div class="square-switch">
                                                    <input type="checkbox" name="notif_pembelian_voucher_terbayar" id="square-switch9" switch="none" value="on" {{ $row->notif_pembelian_voucher_terbayar === 'on' ? 'checked' : '' }} />
                                                    <label for="square-switch9" data-on-label="On" data-off-label="Off"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label for="buttonadd" class="col-sm-2 col-form-label"></label>
                                    <div class="col-sm-10">
                                        <button class="btn btn-primary" id="buttonadd" type="submit"><i class="uil uil-edit"></i> Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
