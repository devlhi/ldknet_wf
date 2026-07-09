<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h4>{{ $title }}</h4>
                        <ul class="list-group mb-4">
                            <li class="list-group-item"><strong>Kode:</strong> {{ $invoice->code }}</li>
                            <li class="list-group-item"><strong>ID Pelanggan:</strong> {{ $invoice->idpel }}</li>
                            <li class="list-group-item"><strong>Nama:</strong> {{ $invoice->nama }}</li>
                            <li class="list-group-item"><strong>Paket:</strong> {{ $invoice->package }}</li>
                            <li class="list-group-item"><strong>Harga:</strong> {{ function_exists('rupiah') ? rupiah($invoice->price) : $invoice->price }}</li>
                            <li class="list-group-item"><strong>Status:</strong> {{ $invoice->status }}</li>
                        </ul>
                        @if ($invoice->status !== 'Paid')
                            <form action="{{ url('tagihan/process') }}" method="post">
                                @csrf
                                <input type="hidden" name="invoice" value="{{ $invoice->code }}">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="category" class="form-control js-payment-category" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach ($category as $cat)
                                            <option value="{{ $cat->category }}">{{ $cat->name ?? $cat->nama ?? $cat->category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Metode Pembayaran</label>
                                    <select name="service" class="form-control js-payment-service" required>
                                        <option value="">Pilih Metode</option>
                                        @foreach ($method as $item)
                                            <option value="{{ $item->id }}" data-category="{{ $item->category }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Proses Pembayaran</button>
                            </form>
                        @endif
                        <a href="{{ url('cek') }}" class="btn btn-secondary mt-3">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script>
        $(function () {
            function filterPaymentMethods() {
                var category = $('.js-payment-category').val();

                $('.js-payment-service option').each(function () {
                    var option = $(this);
                    var optionCategory = option.data('category');

                    if (! option.val()) {
                        option.prop('hidden', false);
                        return;
                    }

                    option.prop('hidden', category && optionCategory !== category);
                });

                var selected = $('.js-payment-service option:selected');
                if (selected.val() && selected.data('category') !== category) {
                    $('.js-payment-service').val('');
                }
            }

            $('.js-payment-category').on('change', filterPaymentMethods);
            filterPaymentMethods();
        });
    </script>
</body>
</html>
