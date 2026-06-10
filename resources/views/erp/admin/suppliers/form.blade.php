@extends('erp::layouts.app')

@section('title', $supplier->exists ? __('Tedarikçi Düzenle') : __('Yeni Tedarikçi'))
@section('page-title', $supplier->exists ? __('Tedarikçi Düzenle') : __('Yeni Tedarikçi'))

@section('content')
    <section class="crm-admin-page" data-crm-module="erp-suppliers">
        @include('erp::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('ERP / Tedarikçiler') }}</p>
                <h1>{{ $supplier->exists ? __('Tedarikçi Düzenle') : __('Yeni Tedarikçi') }}</h1>
            </div>
            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('erp.suppliers.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button>
            </div>
        </header>

        <form method="POST" action="{{ $supplier->exists ? route('erp.suppliers.update', $supplier) : route('erp.suppliers.store') }}">
            @csrf
            @if($supplier->exists) @method('PUT') @endif

            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Ad / Firma Adı')" :value="old('name', $supplier->name)" required /></div>
                    <div class="col-md-4">
                        <x-admin-panel::select name="status" :label="__('Durum')" :options="['active'=>__('Aktif'),'inactive'=>__('Pasif')]" :selected="old('status', $supplier->status ?? 'active')" />
                    </div>
                    <div class="col-md-6"><x-admin-panel::input name="email" type="email" :label="__('E-posta')" :value="old('email', $supplier->email)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="phone" :label="__('Telefon')" :value="old('phone', $supplier->phone)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="tax_number" :label="__('Vergi No')" :value="old('tax_number', $supplier->tax_number)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="contact_person" :label="__('İletişim Kişisi')" :value="old('contact_person', $supplier->contact_person)" /></div>
                    <div class="col-12"><x-admin-panel::input name="address" :label="__('Adres')" :value="old('address', $supplier->address)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="payment_terms_days" type="number" :label="__('Ödeme Vadesi (gün)')" :value="old('payment_terms_days', $supplier->payment_terms_days ?? 30)" /></div>
                </div>
            </x-admin-panel::card>

            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $supplier->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.suppliers.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
