@extends('erp::layouts.app')

@section('title', __('Yeni BOM'))
@section('page-title', __('Yeni Ürün Ağacı'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.boms.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('BOM Listesine Dön') }}</x-admin-panel::button>
    </div>

    <form method="POST" action="{{ route('erp.boms.store') }}">
        @csrf
        <x-admin-panel::card class="mb-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <x-admin-panel::select name="product_id" :label="__('Mamul Ürün')"
                        :options="$products->pluck('name','id')->toArray()" :selected="old('product_id')" required />
                    @error('product_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <x-admin-panel::input name="version" :label="__('Versiyon')" :value="old('version','1.0')" required />
                </div>
                <div class="col-md-2">
                    <x-admin-panel::input name="quantity" :label="__('Üretilen Miktar')" type="number" step="0.001" min="0.001" :value="old('quantity','1')" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="notes" :label="__('Notlar')" :value="old('notes')" />
                </div>
            </div>
        </x-admin-panel::card>

        <x-admin-panel::card class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">{{ __('Bileşenler (Hammadde/Yarı Mamul)') }}</h6>
                <x-admin-panel::button type="button" id="add-component" variant="outline" icon="plus" size="sm">{{ __('Bileşen Ekle') }}</x-admin-panel::button>
            </div>

            <div id="components-container">
                @php $oldComponents = old('components', [['component_id'=>'','quantity'=>'','notes'=>'']]) @endphp
                @foreach($oldComponents as $i => $comp)
                    <div class="component-row row g-2 mb-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">{{ __('Bileşen') }}</label>
                            <select name="components[{{ $i }}][component_id]" class="form-select form-select-sm" required>
                                <option value="">{{ __('Ürün Seçin') }}</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ ($comp['component_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('Miktar') }}</label>
                            <input type="number" step="0.001" name="components[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $comp['quantity'] ?? '' }}" min="0.001" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('Not') }}</label>
                            <input type="text" name="components[{{ $i }}][notes]" class="form-control form-control-sm" value="{{ $comp['notes'] ?? '' }}" />
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-component w-100">×</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin-panel::card>

        <div class="d-flex justify-content-end gap-2">
            <x-admin-panel::button href="{{ route('erp.boms.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
            <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function() {
    let idx = {{ count($oldComponents) }};
    const opts = `@foreach($products as $p)<option value="{{ $p->id }}">{{ addslashes($p->name) }} ({{ $p->sku }})</option>@endforeach`;

    document.getElementById('add-component').addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'component-row row g-2 mb-2 align-items-end';
        div.innerHTML = `
            <div class="col-md-5"><label class="form-label small">{{ __('Bileşen') }}</label>
            <select name="components[${idx}][component_id]" class="form-select form-select-sm" required><option value="">{{ __('Ürün Seçin') }}</option>${opts}</select></div>
            <div class="col-md-2"><label class="form-label small">{{ __('Miktar') }}</label>
            <input type="number" step="0.001" name="components[${idx}][quantity]" class="form-control form-control-sm" min="0.001" required /></div>
            <div class="col-md-4"><label class="form-label small">{{ __('Not') }}</label>
            <input type="text" name="components[${idx}][notes]" class="form-control form-control-sm" /></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-component w-100">×</button></div>`;
        document.getElementById('components-container').appendChild(div);
        div.querySelector('.remove-component').addEventListener('click', () => div.remove());
        idx++;
    });

    document.querySelectorAll('.remove-component').forEach(btn => btn.addEventListener('click', () => btn.closest('.component-row').remove()));
})();
</script>
@endpush
