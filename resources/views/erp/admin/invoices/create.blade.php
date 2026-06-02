@extends('erp::layouts.app')

@section('title', __('Yeni Fatura'))
@section('page-title', __('Yeni Fatura'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.invoices.store') }}">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <x-admin-panel::select name="type" :label="__('Fatura Tipi')" required
                        :options="['sale' => __('Satış Faturası'), 'purchase' => __('Alış Faturası'), 'credit_note' => __('Kredi Notu')]"
                        :selected="old('type', 'sale')" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="issue_date" type="date" :label="__('Düzenleme Tarihi')" :value="old('issue_date', date('Y-m-d'))" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="due_date" type="date" :label="__('Vade Tarihi')" :value="old('due_date', date('Y-m-d', strtotime('+30 days')))" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="reference" :label="__('Referans No')" :value="old('reference')" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="discount_amount" type="number" step="0.01" :label="__('İndirim Tutarı')" :value="old('discount_amount', '0')" />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="notes" :label="__('Notlar')" rows="2">{{ old('notes') }}</x-admin-panel::textarea>
                </div>
            </div>

            <h6 class="fw-semibold mb-3">{{ __('Fatura Kalemleri') }}</h6>
            <x-admin-panel::table :headers="[__('Açıklama'), __('Ürün'), __('Miktar'), __('Birim Fiyat'), __('İndirim %'), __('KDV %'), '']">
                <tbody id="items-body">
                    @foreach(old('items', [[]]) as $i => $item)
                        <tr class="item-row">
                            <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $item['description'] ?? '' }}" required></td>
                            <td>
                                <select name="items[{{ $i }}][product_id]" class="form-control form-control-sm">
                                    <option value="">-</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" {{ ($item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.001" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $item['quantity'] ?? '1' }}" required min="0.001"></td>
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
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Fatura Oluştur') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.invoices.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>

    @php $productsJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name]); @endphp
    <script>
        (function() {
            var erpProducts = {!! json_encode($productsJson) !!};
            var selectLabel = {!! json_encode(__('-')) !!};

            function buildProductSelect(name, sel) {
                var opts = '<option value="">' + selectLabel + '</option>';
                erpProducts.forEach(function(p) {
                    opts += '<option value="' + p.id + '"' + (sel == p.id ? ' selected' : '') + '>' + p.name + '</option>';
                });
                return '<select name="' + name + '" class="form-control form-control-sm">' + opts + '</select>';
            }

            document.getElementById('add-row').addEventListener('click', function() {
                var body = document.getElementById('items-body');
                var idx  = body.querySelectorAll('.item-row').length;
                var tr   = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML =
                    '<td><input type="text" name="items[' + idx + '][description]" class="form-control form-control-sm" required></td>' +
                    '<td>' + buildProductSelect('items[' + idx + '][product_id]') + '</td>' +
                    '<td><input type="number" step="0.001" name="items[' + idx + '][quantity]" class="form-control form-control-sm" value="1" required min="0.001"></td>' +
                    '<td><input type="number" step="0.01" name="items[' + idx + '][unit_price]" class="form-control form-control-sm" required min="0"></td>' +
                    '<td><input type="number" step="0.01" name="items[' + idx + '][discount_rate]" class="form-control form-control-sm" value="0" min="0" max="100"></td>' +
                    '<td><input type="number" step="0.01" name="items[' + idx + '][tax_rate]" class="form-control form-control-sm" value="20" min="0" max="100"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">x</button></td>';
                body.appendChild(tr);
            });
        })();
    </script>
@endsection
