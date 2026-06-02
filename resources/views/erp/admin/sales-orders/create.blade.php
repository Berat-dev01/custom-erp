@extends('erp::layouts.app')

@section('title', __('Yeni Satış Siparişi'))
@section('page-title', __('Yeni Satış Siparişi'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.sales-orders.store') }}">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <x-admin-panel::select name="customer_id" :label="__('Müşteri')" required
                        :options="$customers->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('customer_id', request('customer_id'))" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="warehouse_id" :label="__('Sevk Deposu')" required
                        :options="$warehouses->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('warehouse_id')" />
                </div>
                <div class="col-md-2">
                    <x-admin-panel::input name="order_date" type="date" :label="__('Sipariş Tarihi')" :value="old('order_date', date('Y-m-d'))" required />
                </div>
                <div class="col-md-2">
                    <x-admin-panel::input name="requested_delivery_date" type="date" :label="__('İstenen Teslimat')" :value="old('requested_delivery_date')" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="discount_amount" type="number" step="0.01" :label="__('Sipariş İndirimi')" :value="old('discount_amount', '0')" />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="notes" :label="__('Notlar')" rows="2">{{ old('notes') }}</x-admin-panel::textarea>
                </div>
            </div>

            <h6 class="fw-semibold mb-3">{{ __('Sipariş Kalemleri') }}</h6>
            <x-admin-panel::table :headers="[__('Ürün'), __('Miktar'), __('Birim Fiyat'), __('İndirim %'), __('KDV %'), '']">
                <tbody id="items-body">
                    @foreach(old('items', [[]]) as $i => $item)
                        <tr class="item-row">
                            <td>
                                <select name="items[{{ $i }}][product_id]" class="form-control form-control-sm" required>
                                    <option value="">{{ __('Seçiniz') }}</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.001" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $item['quantity'] ?? '' }}" required min="0.001"></td>
                            <td><input type="number" step="0.01" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm" value="{{ $item['unit_price'] ?? '' }}" required min="0"></td>
                            <td><input type="number" step="0.01" name="items[{{ $i }}][discount_rate]" class="form-control form-control-sm" value="{{ $item['discount_rate'] ?? '0' }}" min="0" max="100"></td>
                            <td><input type="number" step="0.01" name="items[{{ $i }}][tax_rate]" class="form-control form-control-sm" value="{{ $item['tax_rate'] ?? '20' }}" min="0" max="100"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin-panel::table>

            <div class="mt-2">
                <x-admin-panel::button type="button" variant="outline" icon="plus" id="add-row">{{ __('Satır Ekle') }}</x-admin-panel::button>
            </div>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Sipariş Oluştur') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.sales-orders.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>

    @php $productsJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'sale_price' => $p->sale_price, 'tax_rate' => $p->tax_rate]); @endphp
    <script>
        (function() {
            var erpProducts  = {!! json_encode($productsJson) !!};
            var placeholder  = {!! json_encode(__('Seçiniz')) !!};

            function buildOpts(sel) {
                var html = '<option value="">' + placeholder + '</option>';
                erpProducts.forEach(function(p) {
                    html += '<option value="' + p.id + '"' + (sel == p.id ? ' selected' : '') + ' data-price="' + p.sale_price + '" data-tax="' + p.tax_rate + '">' + p.name + ' (' + p.sku + ')</option>';
                });
                return html;
            }

            document.getElementById('add-row').addEventListener('click', function() {
                var body = document.getElementById('items-body');
                var idx  = body.querySelectorAll('.item-row').length;
                var tr   = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML =
                    '<td><select name="items[' + idx + '][product_id]" class="form-control form-control-sm product-select" required>' + buildOpts() + '</select></td>' +
                    '<td><input type="number" step="0.001" name="items[' + idx + '][quantity]" class="form-control form-control-sm" value="1" required min="0.001"></td>' +
                    '<td><input type="number" step="0.01" name="items[' + idx + '][unit_price]" class="form-control form-control-sm item-price" required min="0"></td>' +
                    '<td><input type="number" step="0.01" name="items[' + idx + '][discount_rate]" class="form-control form-control-sm" value="0" min="0" max="100"></td>' +
                    '<td><input type="number" step="0.01" name="items[' + idx + '][tax_rate]" class="form-control form-control-sm item-tax" value="20" min="0" max="100"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">x</button></td>';
                body.appendChild(tr);

                tr.querySelector('.product-select').addEventListener('change', function() {
                    var opt = this.options[this.selectedIndex];
                    var row = this.closest('tr');
                    if (opt.dataset.price) row.querySelector('.item-price').value = opt.dataset.price;
                    if (opt.dataset.tax)   row.querySelector('.item-tax').value   = opt.dataset.tax;
                });
            });
        })();
    </script>
@endsection
