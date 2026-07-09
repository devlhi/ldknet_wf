<script>
(function () {
    var productOptions = `<option value="">-- Manual --</option>@foreach ($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->sale_price }}" data-account="{{ $p->income_account_id }}" data-name="{{ $p->name }}">{{ $p->name }}</option>@endforeach`;
    var accountOptions = `<option value="">-- Default --</option>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach`;
    var rowIndex = {{ count($oldItems) }};

    function fmt(n) { return (n || 0).toLocaleString('id-ID'); }

    function recalc() {
        var subtotal = 0;
        document.querySelectorAll('#itemsBody tr').forEach(function (tr) {
            var qty = parseFloat(tr.querySelector('.qty-in').value) || 0;
            var price = parseFloat(tr.querySelector('.price-in').value) || 0;
            var amount = qty * price;
            tr.querySelector('.amount-cell').textContent = fmt(amount);
            subtotal += amount;
        });
        var discount = parseFloat(document.getElementById('discountIn').value) || 0;
        var tax = parseFloat(document.getElementById('taxIn').value) || 0;
        document.getElementById('subtotalCell').textContent = fmt(subtotal);
        document.getElementById('totalCell').textContent = fmt(subtotal - discount + tax);
    }

    function addItem() {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="items[' + rowIndex + '][product_id]" class="form-select form-select-sm prod-select">' + productOptions + '</select></td>' +
            '<td><input type="text" name="items[' + rowIndex + '][description]" class="form-control form-control-sm desc-in" required></td>' +
            '<td><select name="items[' + rowIndex + '][account_id]" class="form-select form-select-sm">' + accountOptions + '</select></td>' +
            '<td><input type="number" step="0.01" name="items[' + rowIndex + '][qty]" class="form-control form-control-sm text-end qty-in" value="1"></td>' +
            '<td><input type="number" step="0.01" name="items[' + rowIndex + '][price]" class="form-control form-control-sm text-end price-in" value="0"></td>' +
            '<td class="text-end amount-cell">0</td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-item"><i class="uil uil-trash"></i></button></td>';
        document.getElementById('itemsBody').appendChild(tr);
        rowIndex++;
    }

    document.getElementById('addItem').addEventListener('click', addItem);

    document.addEventListener('input', function (e) {
        if (e.target.matches('.qty-in, .price-in, #discountIn, #taxIn')) recalc();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('prod-select')) {
            var opt = e.target.selectedOptions[0];
            var tr = e.target.closest('tr');
            if (opt && opt.value) {
                tr.querySelector('.price-in').value = opt.dataset.price || 0;
                if (!tr.querySelector('.desc-in').value) tr.querySelector('.desc-in').value = opt.dataset.name || '';
                var acc = opt.dataset.account;
                if (acc) { var sel = tr.querySelector('select[name*="[account_id]"]'); if (sel) sel.value = acc; }
            }
            recalc();
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.del-item');
        if (btn) {
            if (document.querySelectorAll('#itemsBody tr').length > 1) {
                btn.closest('tr').remove();
                recalc();
            }
        }
    });

    recalc();
})();
</script>
