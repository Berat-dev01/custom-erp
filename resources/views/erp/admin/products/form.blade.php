@extends('erp::layouts.app')
@section('title', $product->exists ? __('Ürün Düzenle') : __('Yeni Ürün'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-products">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Ürünler</p><h1>{{ $product->exists ? __('Ürün Düzenle') : __('Yeni Ürün') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.products.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $product->exists ? route('erp.products.update', $product) : route('erp.products.store') }}">
            @csrf
            @if($product->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Ürün Adı')" :value="old('name', $product->name)" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="sku" :label="__('SKU')" :value="old('sku', $product->sku)" /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="category_id" :label="__('Kategori')" :options="$categories" :selected="old('category_id', $product->category_id)" placeholder="Kategori seçin" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="unit_id" :label="__('Birim')" :options="$units" :selected="old('unit_id', $product->unit_id)" placeholder="Birim seçin" />
                    </div>
                    <div class="col-md-4"><x-admin-panel::input name="purchase_price" type="number" step="0.01" :label="__('Alış Fiyatı')" :value="old('purchase_price', $product->purchase_price ?? 0)" /></div>
                    <div class="col-md-4"><x-admin-panel::input name="sale_price" type="number" step="0.01" :label="__('Satış Fiyatı')" :value="old('sale_price', $product->sale_price ?? 0)" /></div>
                    <div class="col-md-4"><x-admin-panel::input name="tax_rate" type="number" step="0.01" :label="__('KDV %')" :value="old('tax_rate', $product->tax_rate ?? 20)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="reorder_point" type="number" :label="__('Reorder Noktası')" :value="old('reorder_point', $product->reorder_point ?? 0)" /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="is_active" :label="__('Durum')" :options="['1'=>__('Aktif'),'0'=>__('Pasif')]" :selected="old('is_active', $product->is_active ?? '1')" />
                    </div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $product->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.products.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
