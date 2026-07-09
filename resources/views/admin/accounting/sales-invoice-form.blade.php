@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        @php
            $isEdit = $invoice !== null;
            $action = $isEdit ? url('admin/accounting/sales/update/'.$invoice->id) : url('admin/accounting/sales/store');
            $oldItems = old('items');
            if (! $oldItems && $isEdit) {
                $oldItems = $invoice->items->map(fn ($i) => [
                    'product_id' => $i->product_id,
                    'account_id' => $i->account_id,
                    'description' => $i->description,
                    'qty' => (float) $i->qty,
                    'price' => (float) $i->price,
                ])->toArray();
            }
            if (! $oldItems) {
                $oldItems = [['product_id' => '', 'account_id' => '', 'description' => '', 'qty' => 1, 'price' => 0]];
            }
        @endphp

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0"><i class="uil uil-file-alt me-1"></i> {{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ $action }}" method="POST" id="invForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Nomor Faktur</label>
                                    <input type="text" name="number" class="form-control" value="{{ old('number', $suggestedNumber) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Pelanggan</label>
                                    <select name="contact_id" class="form-select" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach ($contacts as $c)
                                            <option value="{{ $c->id }}" {{ old('contact_id', $isEdit ? $invoice->contact_id : '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Tanggal</label>
                                    <input type="date" name="date" class="form-control" value="{{ old('date', $isEdit ? $invoice->date->toDateString() : now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Jatuh Tempo</label>
                                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $isEdit && $invoice->due_date ? $invoice->due_date->toDateString() : '') }}">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:22%;">Produk/Jasa</th>
                                            <th style="width:25%;">Deskripsi</th>
                                            <th style="width:15%;">Akun Pendapatan</th>
                                            <th style="width:10%;">Qty</th>
                                            <th style="width:14%;">Harga</th>
                                            <th style="width:14%;" class="text-end">Jumlah</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        @foreach ($oldItems as $i => $item)
                                            <tr>
                                                <td>
                                                    <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm prod-select">
                                                        <option value="">-- Manual --</option>
                                                        @foreach ($products as $p)
                                                            <option value="{{ $p->id }}" data-price="{{ $p->sale_price }}" data-account="{{ $p->income_account_id }}" data-name="{{ $p->name }}" {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm desc-in" value="{{ $item['description'] ?? '' }}" required></td>
                                                <td>
                                                    <select name="items[{{ $i }}][account_id]" class="form-select form-select-sm">
                                                        <option value="">-- Default --</option>
                                                        @foreach ($accounts as $a)
                                                            <option value="{{ $a->id }}" {{ ($item['account_id'] ?? '') == $a->id ? 'selected' : '' }}>{{ $a->code }} - {{ $a->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="number" step="0.01" name="items[{{ $i }}][qty]" class="form-control form-control-sm text-end qty-in" value="{{ $item['qty'] ?? 1 }}"></td>
                                                <td><input type="number" step="0.01" name="items[{{ $i }}][price]" class="form-control form-control-sm text-end price-in" value="{{ $item['price'] ?? 0 }}"></td>
                                                <td class="text-end amount-cell">0</td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-item"><i class="uil uil-trash"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addItem"><i class="uil uil-plus"></i> Tambah Baris</button>

                            <div class="row">
                                <div class="col-md-7">
                                    <label class="form-label">Catatan</label>
                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $isEdit ? $invoice->notes : '') }}</textarea>
                                    <label class="form-label mt-2">Referensi</label>
                                    <input type="text" name="reference" class="form-control" value="{{ old('reference', $isEdit ? $invoice->reference : '') }}">
                                </div>
                                <div class="col-md-5">
                                    <table class="table table-sm">
                                        <tr><th>Subtotal</th><td class="text-end" id="subtotalCell">0</td></tr>
                                        <tr><th>Diskon</th><td><input type="number" step="0.01" name="discount" id="discountIn" class="form-control form-control-sm text-end" value="{{ old('discount', $isEdit ? $invoice->discount : 0) }}"></td></tr>
                                        <tr><th>Pajak (PPN)</th><td><input type="number" step="0.01" name="tax" id="taxIn" class="form-control form-control-sm text-end" value="{{ old('tax', $isEdit ? $invoice->tax : 0) }}"></td></tr>
                                        <tr class="fw-bold"><th>TOTAL</th><td class="text-end" id="totalCell">0</td></tr>
                                    </table>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Simpan Faktur</button>
                                <a href="{{ url('admin/accounting/sales') }}" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('admin.accounting.partials.sales-form-script')
@endsection
