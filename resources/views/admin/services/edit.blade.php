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
                    <h4 class="mb-0">Services Edit</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="{{ url('admin/services/update') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Paket</label>
                                        <input type="hidden" name="target" value="{{ $service->id }}">
                                        @if ($isPaketExist)
                                            <input type="hidden" name="nama" value="{{ $service->paket }}">
                                            <input type="text" name="nama_display" class="form-control" value="{{ $service->paket }}" disabled>
                                        @else
                                            <input type="text" name="nama" class="form-control" value="{{ $service->paket }}">
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom02">Harga</label>
                                        <input type="text" name="harga" class="form-control" value="{{ $service->harga }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom03">PPN ( Isikan dengan angka saja )</label>
                                        <input type="text" name="ppn" class="form-control" value="{{ $service->ppn }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom03">Mode</label>
                                        @if ($isPaketExist)
                                            <input type="hidden" name="mode" value="{{ $service->mode }}">
                                            <select class="form-select" aria-label="Default select example" name="mode_display" disabled>
                                                <option value="{{ $service->mode }}" selected>{{ $service->mode }}</option>
                                            </select>
                                        @else
                                            <select class="form-select" aria-label="Default select example" name="mode">
                                                <option value="pppoe" {{ $service->mode == 'pppoe' ? 'selected' : '' }}>PPPOE</option>
                                                <option value="hotspot" {{ $service->mode == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="validationCustom03">Status</label>
                                        @if ($isPaketExist)
                                            <input type="hidden" name="status" value="{{ $service->status }}">
                                            <select class="form-select" aria-label="Default select example" name="status_display" disabled>
                                                <option value="Tersedia" {{ $service->status == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                                <option value="Tidak Tersedia" {{ $service->status == 'Tidak Tersedia' ? 'selected' : '' }}>Tidak Tersedia</option>
                                            </select>
                                        @else
                                            <select class="form-select" aria-label="Default select example" name="status">
                                                <option value="Tersedia" {{ $service->status == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                                <option value="Tidak Tersedia" {{ $service->status == 'Tidak Tersedia' ? 'selected' : '' }}>Tidak Tersedia</option>
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">Update</button>
                        </form>
                    </div>
                </div>
                <!-- end card -->
            </div> <!-- end col -->
        </div>
    </div>
</div>
@endsection
