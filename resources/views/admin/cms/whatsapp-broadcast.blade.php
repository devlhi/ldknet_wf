@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">

                    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div class="float-left">
                                        <h6 class="modal-title" id="custom-width-modalLabel"><i class="fa fa-plus"></i> Kirim Informasi by media</h6>
                                    </div>
                                    <div class="float-right">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                </div>
                                <div class="modal-body">

                                    <form class="form-horizontal" action="{{ url('admin/cms/broadcast/sendmedia') }}" role="form" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Url Media </label>
                                            <div class="col-md-12">
                                                <input type="text" name="url" class="form-control" placeholder="Url Media">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Media Type</label>
                                            <div class="col-md-12">
                                                <input type="text" name="type" class="form-control" placeholder="audio / video / image / pdf / xls /xlsx /doc /docx /zip">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-2 control-label">Caption </label>
                                            <div class="col-md-12">
                                                <textarea rows="20" name="caption" class="form-control"></textarea>
                                            </div>
                                        </div>


                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Kembali</button>
                                            <button type="reset" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="fa fa-refresh"></i> Reset</button>
                                            <button type="submit" class="btn btn-success btn-bordered waves-effect w-md waves-light" name="add"><i class="fa fa-plus"></i> Kirim</button>
                                        </div>
                                    </form>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->
                    <div>
                        <button type="button" class="btn btn-primary waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#myModal">
                            <i class="mdi mdi-plus me-1"></i> Broadcast Information By Media </button>
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
                    <div class="card">
                        <div class="card-body">

                            <h4 class="card-title">Broadcast Information </h4>
                            <p class="card-title-desc">Fitur ini berguna untuk mengirimkan informasi kepada semua pelanggan via whatsapp.</p>

                            <form method="post" action="{{ url('admin/cms/broadcast/whatsapp/send') }}">
                                @csrf
                                <textarea id="elm1" name="message"></textarea>
                                <br />
                                <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="uil uil-megaphone me-2"></i> Send Broadcast
                                </button>
                            </form>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
@endsection
