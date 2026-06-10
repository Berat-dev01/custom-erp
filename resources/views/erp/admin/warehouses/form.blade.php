@extends('erp::layouts.app')
@section('title', $warehouse->exists ? __('Depo Düzenle') : __('Yeni Depo'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-warehouses">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Depolar</p><h1>{{ $warehouse->exists ? __('Depo Düzenle') : __('Yeni Depo') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.warehouses.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $warehouse->exists ? route('erp.warehouses.update', $warehouse) : route('erp.warehouses.store') }}">
            @csrf
            @if($warehouse->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Depo Adı')" :value="old('name', $warehouse->name)" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="location" :label="__('Konum')" :value="old('location', $warehouse->location)" /></div>
                    <div class="col-12"><x-admin-panel::input name="address" :label="__('Adres')" :value="old('address', $warehouse->address)" /></div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Varsayılan Depo') }}</label>
                        <div><input type="checkbox" name="is_default" value="1" {{ old('is_default', $warehouse->is_default) ? 'checked' : '' }} class="form-check-input"></div>
                    </div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $warehouse->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.warehouses.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
