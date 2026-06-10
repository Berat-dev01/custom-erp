@extends('erp::layouts.app')

@section('title', $customer->exists ? __('Müşteri Düzenle') : __('Yeni Müşteri'))
@section('page-title', $customer->exists ? __('Müşteri Düzenle') : __('Yeni Müşteri'))

@section('content')
    <section class="crm-admin-page" data-crm-module="erp-customers">
        @include('erp::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('ERP / Müşteriler') }}</p>
                <h1>{{ $customer->exists ? __('Müşteri Düzenle') : __('Yeni Müşteri') }}</h1>
            </div>
            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('erp.customers.index')" variant="ghost" icon="arrow-left">
                    {{ __('Geri') }}
                </x-admin-panel::button>
            </div>
        </header>

        <form method="POST" action="{{ $customer->exists ? route('erp.customers.update', $customer) : route('erp.customers.store') }}">
            @csrf
            @if($customer->exists) @method('PUT') @endif

            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8">
                        <x-admin-panel::input name="name" :label="__('Ad / Firma Adı')" :value="old('name', $customer->name)" required />
                    </div>
                    <div class="col-md-4">
                        <x-admin-panel::select name="status" :label="__('Durum')"
                            :options="['active' => __('Aktif'), 'inactive' => __('Pasif')]"
                            :selected="old('status', $customer->status ?? 'active')" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input name="email" type="email" :label="__('E-posta')" :value="old('email', $customer->email)" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input name="phone" :label="__('Telefon')" :value="old('phone', $customer->phone)" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input name="tax_number" :label="__('Vergi No / TC No')" :value="old('tax_number', $customer->tax_number)" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input name="contact_person" :label="__('İletişim Kişisi')" :value="old('contact_person', $customer->contact_person)" />
                    </div>
                    <div class="col-12">
                        <x-admin-panel::input name="address" :label="__('Adres')" :value="old('address', $customer->address)" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input name="payment_terms_days" type="number" :label="__('Ödeme Vadesi (gün)')" :value="old('payment_terms_days', $customer->payment_terms_days ?? 30)" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input name="credit_limit" type="number" step="0.01" :label="__('Kredi Limiti')" :value="old('credit_limit', $customer->credit_limit ?? 0)" />
                    </div>
                </div>
            </x-admin-panel::card>

            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">
                    {{ $customer->exists ? __('Güncelle') : __('Kaydet') }}
                </x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.customers.index')" variant="ghost">
                    {{ __('İptal') }}
                </x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
