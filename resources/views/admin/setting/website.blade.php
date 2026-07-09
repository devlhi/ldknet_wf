@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    @foreach ($content as $row)

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Pengaturan Website </h4>

                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-message" role="alert">
                                        @foreach ($errors->all() as $err)
                                            {{ $err }}
                                        @endforeach
                                    </div>
                                @endif

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

                                <form action="{{ url('admin/setting/website/update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" value="{{ $row->title }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Logo Saat Ini</label>
                                        <div>
                                            <a href="{{ asset('assets/logo/'.$row->logo) }}" target="_blank">
                                                <img id="logo-current" src="{{ asset('assets/logo/'.$row->logo) }}" alt="Logo" style="max-width:300px;max-height:120px;object-fit:contain;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px;">
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="image">Upload Logo Baru</label>
                                        <input type="file" class="form-control" name="image" id="image"
                                               accept="image/png,image/jpeg,image/webp">
                                        <small class="text-muted d-block mt-1">
                                            Format PNG / JPG / WEBP &middot; Maks 5MB &middot; disarankan latar transparan.
                                        </small>
                                        <div class="mt-2" id="logo-preview-wrap" style="display:none;">
                                            <span class="d-block text-muted mb-1" style="font-size:.8rem;">Pratinjau logo baru:</span>
                                            <img id="logo-preview" src="" alt="Pratinjau" style="max-width:300px;max-height:120px;object-fit:contain;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px;">
                                        </div>
                                    </div>


                                    <div>
                                        <div>

                                            <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                                Update
                                            </button>

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

@section('scripts')
    <script>
        document.getElementById('image')?.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            var wrap = document.getElementById('logo-preview-wrap');
            var img = document.getElementById('logo-preview');
            if (!file || !file.type.startsWith('image/')) {
                wrap.style.display = 'none';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                img.src = ev.target.result;
                wrap.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    </script>
@endsection
