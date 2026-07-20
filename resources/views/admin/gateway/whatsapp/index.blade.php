@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Whatsapp Gateway</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row">

                <div class="col-12">
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
                            <div class="alert alert-info mb-3">
                                Hanya satu WhatsApp gateway yang boleh aktif. Jika satu gateway diubah menjadi ON, gateway lain otomatis OFF.
                            </div>
                            <div class="mb-3 d-flex gap-2">
                                <a href="{{ url('admin/whatsapp/setup') }}" class="btn btn-primary btn-sm">
                                    <i class="uil uil-plus"></i> Tambah Gateway
                                </a>
                                <a href="{{ url('admin/whatsapp/setup?provider=meta') }}" class="btn btn-success btn-sm">
                                    <i class="uil uil-whatsapp"></i> Tambah Meta Official
                                </a>
                            </div>

                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama </th>
                                        <th>Mode</th>
                                        <th>Type</th>
                                        <th>Provider</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($content as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $row->nama }}</td>
                                            <td><span class="badge bg-{{ $row->mode === 'on' ? 'success' : 'secondary' }}">{{ strtoupper($row->mode) }}</span></td>
                                            <td>{{ $row->type }}</td>
                                            <td>
                                                @if (\App\Support\WhatsAppGatewayResolver::isMeta($row))
                                                    <span class="badge bg-primary">Meta Official</span>
                                                @else
                                                    <span class="badge bg-info">Gateway Lama</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ url('admin/whatsapp/edit/'.$row->id) }}" class="btn btn-sm btn-primary"><i class='uil uil-edit'></i> Edit</a>
                                                @if (\App\Support\WhatsAppGatewayResolver::isMeta($row))
                                                    <a href="{{ url('admin/whatsapp/meta/templates') }}" class="btn btn-sm btn-success"><i class="uil uil-comment-alt-lines"></i> Templates</a>
                                                @endif

                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-{{ $row->id }}"><i class="uil uil-trash"></i> Delete</button>

                                                <!--- Modal Delete -->
                                                <div class="modal fade" id="delete-{{ $row->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Layanan</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Apakah Anda ingin menghapus gateway <b><u>{{ $row->nama }}</u></b>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <form method="post" action="{{ url('admin/whatsapp/delete/'.$row->id) }}">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-primary">Yes</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($content->isEmpty())
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada WhatsApp gateway.</td></tr>
                                    @endif
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection
