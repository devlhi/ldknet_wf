@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">{{ $title }}</h4>
                            <div>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAreaModal">
                                <i class="uil uil-plus"></i> Tambah Area
                                </button>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">
                                @foreach (session('success') as $msg)
                                    <p class="mb-0">{{ $msg }}</p>
                                @endforeach
                            </div>
                        @endif
                        @if (session('auth_errors'))
                            <div class="alert alert-danger">
                                @foreach (session('auth_errors') as $msg)
                                    <p class="mb-0">{{ $msg }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Nama Area</th>
                                        <th>Jumlah ODP</th>
                                        <th>Jumlah Pelanggan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($area as $i => $row)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $row['kode'] }}</td>
                                            <td>{{ $row['nama'] }}</td>
                                            <td>{{ $row['jumlah_odp'] }}</td>
                                            <td>{{ $row['jumlah_pelanggan'] }}</td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editAreaModal"
                                                   data-id="{{ $row['id'] }}"
                                                   data-kode="{{ $row['kode'] }}"
                                                   data-nama="{{ $row['nama'] }}">Edit</a>
                                                <a href="{{ url('admin/coverage/area/delete/'.$row['id']) }}" class="btn btn-sm btn-danger swal-delete" data-text="Hapus area {{ $row['nama'] }}?">Hapus</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center">Belum ada data area</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAreaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('admin/coverage/area/add') }}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Area</label>
                        <input type="text" name="area" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Area</label>
                        <input type="text" name="kode" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAreaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAreaForm" method="post">
                @csrf
                @method('POST')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Area</label>
                        <input type="text" name="area" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Area</label>
                        <input type="text" name="kode" id="edit_kode" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editAreaModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var kode = button.getAttribute('data-kode');
    var nama = button.getAttribute('data-nama');
    document.getElementById('editAreaForm').action = '{{ url("admin/coverage/area/update") }}/' + id;
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_nama').value = nama;
});

document.querySelectorAll('.swal-delete').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: this.dataset.text || 'Data akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f46a6a',
            cancelButtonColor: '#74788d'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = this.href;
            }
        });
    });
});
</script>
@endsection
