@extends('erp::layouts.app')
@section('title', $asset->exists ? __('Varlık Düzenle') : __('Yeni Varlık'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-assets">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Varlıklar</p><h1>{{ $asset->exists ? __('Varlık Düzenle') : __('Yeni Varlık') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.assets.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $asset->exists ? route('erp.assets.update', $asset) : route('erp.assets.store') }}">
            @csrf
            @if($asset->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Varlık Adı')" :value="old('name', $asset->name)" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="asset_code" :label="__('Kod')" :value="old('asset_code', $asset->asset_code)" /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="category_id" :label="__('Kategori')" :options="$categories" :selected="old('category_id', $asset->category_id)" placeholder="Kategori seçin" required />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="status" :label="__('Durum')" :options="['active'=>__('Aktif'),'in_repair'=>__('Tamirde'),'disposed'=>__('Elden çıkarıldı')]" :selected="old('status', $asset->status ?? 'active')" />
                    </div>
                    <div class="col-md-4"><x-admin-panel::input name="purchase_date" type="date" :label="__('Satın Alma Tarihi')" :value="old('purchase_date', $asset->purchase_date?->format('Y-m-d'))" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="purchase_price" type="number" step="0.01" :label="__('Satın Alma Değeri')" :value="old('purchase_price', $asset->purchase_price)" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="current_value" type="number" step="0.01" :label="__('Güncel Değer')" :value="old('current_value', $asset->current_value)" /></div>
                    <div class="col-md-4"><x-admin-panel::input name="useful_life_years" type="number" :label="__('Faydalı Ömür (yıl)')" :value="old('useful_life_years', $asset->useful_life_years)" /></div>
                    <div class="col-md-4"><x-admin-panel::input name="serial_number" :label="__('Seri No')" :value="old('serial_number', $asset->serial_number)" /></div>
                    <div class="col-md-4">
                        <x-admin-panel::select name="depreciation_method" :label="__('Amortisman Yöntemi')" :options="['straight_line'=>__('Doğrusal'),'declining_balance'=>__('Azalan Bakiye')]" :selected="old('depreciation_method', $asset->depreciation_method ?? 'straight_line')" />
                    </div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $asset->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.assets.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
