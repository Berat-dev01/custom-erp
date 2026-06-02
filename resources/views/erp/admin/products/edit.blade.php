@extends('erp::layouts.app')

@section('title', __('Ürün Düzenle') . ' — ' . $product->name)
@section('page-title', __('Ürün Düzenle'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.products.update', $product) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <x-admin-panel::input name="sku" :label="__('SKU')" :value="old('sku', $product->sku)" required />
                </div>
                <div class="col-md-8">
                    <x-admin-panel::input name="name" :label="__('Ürün Adı')" :value="old('name', $product->name)" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="barcode" :label="__('Barkod')" :value="old('barcode', $product->barcode)" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="category_id" :label="__('Kategori')"
                        :options="$categories->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('category_id', $product->category_id)" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="unit_id" :label="__('Birim')" required
                        :options="$units->map(fn($u) => ['id' => $u->id, 'name' => "{$u->name} ({$u->abbreviation})"])->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('unit_id', $product->unit_id)" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="type" :label="__('Ürün Tipi')" required
                        :options="['product' => __('Ürün'), 'service' => __('Hizmet'), 'consumable' => __('Sarf Malzeme')]"
                        :selected="old('type', $product->type)" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="purchase_price" type="number" step="0.01" :label="__('Alış Fiyatı')" :value="old('purchase_price', $product->purchase_price)" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="sale_price" type="number" step="0.01" :label="__('Satış Fiyatı')" :value="old('sale_price', $product->sale_price)" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="tax_rate" type="number" step="0.01" :label="__('KDV Oranı (%)')" :value="old('tax_rate', $product->tax_rate)" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="reorder_point" type="number" step="0.001" :label="__('Yeniden Sipariş Noktası')" :value="old('reorder_point', $product->reorder_point)" />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="description" :label="__('Açıklama')" rows="3">{{ old('description', $product->description) }}</x-admin-panel::textarea>
                </div>
            </div>

            @if($errors->any())
                <div class="mt-3">
                    <x-admin-panel::alert type="error">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </x-admin-panel::alert>
                </div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Güncelle') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.products.show', $product) }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
