@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->

    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="text-center mb-5">
                        <h4>Paket Internet</h4>

                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-9">
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
                    <div class="row">
                        @foreach ($package as $row)
                            <div class="col-xl-4">
                                <div class="card pricing-box text-center">
                                    <div class="card-body p-4">
                                        <div>

                                            <div class="mt-3">
                                                <h5 class="mb-1">{{ $row->paket }}</h5>
                                            </div>


                                        </div>


                                        <div class="text-center plan-btn my-2">
                                            <a href="{{ url('admin/customer/add/'.$row->id) }}" class="btn btn-primary waves-effect waves-light">Pilih Paket</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach



                    </div>
                    <!-- end row -->
                </div>
            </div>
            <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection
