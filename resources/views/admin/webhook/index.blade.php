@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
                        @if (session('success'))
                            <div class="alert alert-success">
                                @foreach (session('success') as $msg)
                                    <p class="mb-0">{{ $msg }}</p>
                                @endforeach
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Sender</th>
                                        <th>API URL</th>
                                        <th>API Key</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($whatsapp as $row)
                                        <tr>
                                            <td>{{ $row->sender }}</td>
                                            <td>{{ $row->api_url }}</td>
                                            <td>{{ substr($row->api_key, 0, 8) }}...</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editWebhookModal"
                                                        data-id="{{ $row->id }}"
                                                        data-api-url="{{ $row->api_url }}"
                                                        data-api-key="{{ $row->api_key }}"
                                                        data-sender="{{ $row->sender }}">Edit</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center">Belum ada data webhook</td></tr>
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

<div class="modal fade" id="editWebhookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editWebhookForm" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Webhook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sender</label>
                        <input type="text" name="sender" id="wh_sender" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API URL</label>
                        <input type="text" name="api_url" id="wh_api_url" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="text" name="api_key" id="wh_api_key" class="form-control">
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
document.getElementById('editWebhookModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    document.getElementById('editWebhookForm').action = '{{ url("admin/webhook/update") }}';
    document.getElementById('wh_sender').value = button.getAttribute('data-sender');
    document.getElementById('wh_api_url').value = button.getAttribute('data-api-url');
    document.getElementById('wh_api_key').value = button.getAttribute('data-api-key');
    var idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = id;
    document.getElementById('editWebhookForm').appendChild(idInput);
});
</script>
@endsection
