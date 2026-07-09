@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    @foreach ($history as $row)

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">

                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Payment Invoice # {{ $row->code }} </h4>

                        </div>
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
                    </div>
                </div>
                <!-- end page title -->
                <form action="{{ url('admin/finance/invoice/pay') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3 row">
                                        <label for="example-text-input" class="col-md-2 col-form-label">ID Pelanggan</label>
                                        <div class="col-md-10">
                                            <input class="form-control" type="text" value="{{ $row->idpel }}" disabled>

                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="example-text-input" class="col-md-2 col-form-label">Nama Pelanggan</label>
                                        <div class="col-md-10">
                                            <input class="form-control" type="text" value="{{ $row->nama }}" disabled>

                                        </div>
                                    </div>

                                    <div class="mb-3 row">
                                        <label for="example-text-input" class="col-md-2 col-form-label">Paket</label>
                                        <div class="col-md-10">
                                            <input class="form-control" type="text" value="{{ $row->package }}" disabled>
                                            <input type="hidden" name="idpel" id="idpel" value="{{ $row->idpel }}">
                                            <input type="hidden" name="paket" id="paket" value="{{ $row->package }}">
                                            <input type="hidden" name="invoice" id="invoice" value="{{ $row->code }}">

                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="example-search-input" class="col-md-2 col-form-label">Jumlah Tagihan ( + PPN)</label>
                                        <div class="col-md-10">
                                            <input type="text" class="form-control" value="Rp {{ number_format($row->price) }}" disabled>
                                            <input type="hidden" name="price" id="totalnya" value="{{ $row->price }}">
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="username" class="col-sm-2 col-form-label">Metode Pembayaran</label>
                                        <div class="col-sm-10">
                                            <select class="form-select" aria-label="Default select example" id="category" name="category">
                                                <option disabled value="" selected>Pilih metode pembayaran</option>
                                                @foreach ($category as $data)
                                                    <option value="{{ $data->category }}">{{ $data->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="username" class="col-sm-2 col-form-label">Tersedia</label>
                                        <div class="col-sm-10">
                                            <select class="form-select" aria-label="Default select example" name="service" id="service">
                                                <option value="0">Pilih Metode...</option>
                                            </select>
                                        </div>
                                    </div>




                                    <button type="submit" class="btn btn-primary"><i class='uil-location-arrow'></i> Submit</button>


                                </div>
                            </div>
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->
                </form>

            </div>
        </div>
    @endforeach
@endsection

@section('scripts')
    {{-- jQuery sudah dimuat di admin.layout. Jangan muat ulang dari CDN karena
         bisa menimpa plugin yang sudah didaftarkan oleh layout. --}}
    <script type="text/javascript">
        $(document).ready(function() {
            $("#category").change(function() {
                var category = $("#category").val();
                $.ajax({
                    url: '{{ url('ajax/getcategory') }}',
                    data: {
                        category: category,
                        _token: '{{ csrf_token() }}'
                    },
                    type: 'POST',
                    dataType: 'html',
                    success: function(msg) {
                        $("#service").html(msg);
                    }
                });
            });

        });
    </script>
@endsection
