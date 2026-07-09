<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Invoice</title>
    <link rel="shortcut icon" href="{{ asset('assets/logo/logo-230825-85584ca24c.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link id="style" href="{{ asset('assets/css/bootstrap.custom.min.css') }}" rel="stylesheet" />

    <style>
        @media only screen and (max-width: 600px) {
            .mob1 {
                margin-top: 5px !important;
            }
        }

        body {
            padding-top: 80px;
            background-image: url("{{ asset('assets/images/back.jpg') }}");
        }
    </style>
</head>

<body>

    <div class="container" style="max-width:630px;">
        <div class="row p-2 m-1 rounded bg-light mob1 shadow-lg">
            <div class="d-flex justify-content-between mb-2">
                <div class="text-left" style="margin-left:1px;">
                    <span>Pembayaran dengan<strong>
                            <br />
                            {{ $paymentName }}
                        </strong>
                    </span>
                </div>
                <div class="text-right" style="margin-top: auto;margin-bottom: auto;">
                    <img src="https://images.duitku.com/hotlink-ok/DUITKU_LOGO.png" style="height:100%;max-height:26px;" alt="Duitku" />
                </div>
            </div>

            <div class="col-md-7 col-lg-12 mt-2">
                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <h6 class="my-0">No Invoice</h6>
                        <small class="text-muted">{{ $invoiceCode }}</small>
                    </li>

                    @if (! empty($qrString))
                        <li class="list-group-item">
                            <div>
                                <h6 class="my-0">Kode QRIS</h6>
                                <div class="mt-1 w-100 d-flex justify-content-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=175x175&data={{ urlencode($qrString) }}" width="175px" height="175px" alt="QR" />
                                </div>
                            </div>
                        </li>
                    @elseif (! empty($vaNumber))
                        <li class="list-group-item">
                            <div>
                                <h6 class="my-0">Nomor Virtual Account</h6>
                                <div class="input-group mt-1 w-100">
                                    <input type="text" class="form-control" id="kodebayar" value="{{ $vaNumber }}">
                                    <button class="btn btn-outline-secondary text-black" type="button" id="salinkodebayar" onClick="copyToClipboard();">
                                        Salin
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endif

                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <div>
                            <h6>{{ $data }}</h6>
                        </div>
                    </li>

                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <div>
                            <h6>Jumlah Tagihan</h6>
                        </div>
                        <div>
                            <h6>Rp {{ number_format($amount, 0, ',', '.') }}</h6>
                        </div>
                    </li>

                    <li class="list-group-item d-flex justify-content-between">
                        <div>
                            <h6 class="my-0 d-inline">Status</h6>
                        </div>
                        <div>
                            <strong><small class="text-danger">Menunggu Pembayaran</small></strong>
                        </div>
                    </li>

                    <li class="list-group-item d-flex justify-content-between">
                        <h6 class="my-0">Batas Pembayaran</h6>
                        <span class="text-danger"><strong>{{ date('d-m-Y H:i', $expired) }} WIB</strong></span>
                    </li>
                </ul>
            </div>

            <div class="d-flex justify-content-center gap-2">
                <a href="{{ $paymentUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Lanjutkan Pembayaran</a>
                <button type="button" class="btn btn-secondary btn-sm" onclick="returnToMerchant()">Kembali</button>
            </div>

            <footer class="text-center mt-4">
                <small>Secure Payment by <strong>Duitku</strong></small>
            </footer>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            var temp = document.createElement('input');
            var texttoCopy = document.getElementById('kodebayar').value;
            var buttontemp = document.getElementById('salinkodebayar').innerHTML;
            temp.type = 'input';
            temp.setAttribute('value', texttoCopy);
            document.body.appendChild(temp);
            temp.select();
            document.execCommand("copy");
            temp.remove();
            document.getElementById('salinkodebayar').innerHTML = 'Berhasil disalin!';
            document.getElementById("salinkodebayar").disabled = true;
            setTimeout(function() {
                document.getElementById('salinkodebayar').innerHTML = buttontemp;
                document.getElementById("salinkodebayar").disabled = false;
            }, 3000);
        }

        function returnToMerchant() {
            window.open('{{ $backUrl }}', '_self');
        }
    </script>
</body>

</html>
