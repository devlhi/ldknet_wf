@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
                        @foreach ($history as $row)
                            <form action="{{ url('user/invoice/pembayaran') }}" method="post">
                                @csrf
                                <input type="hidden" name="invoice" value="{{ $row->code }}">
                                <input type="hidden" name="paket" value="{{ $row->package }}">
                                <input type="hidden" name="price" value="{{ $row->price }}">
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
                                            <option value="{{ $item->id }}" data-category="{{ $item->category }}">{{ $item->name }} {{ $item->provider ? '('.$item->provider.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kode Kupon</label>
                                    <input type="text" name="codecoupon" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Proses Pembayaran</button>
                                <a href="{{ url('user/invoice') }}" class="btn btn-secondary">Kembali</a>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
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
@endsection
