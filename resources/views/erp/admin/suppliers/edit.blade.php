@extends('erp::layouts.app')

@section('title', __('Tedarikçi Düzenle'))
@section('page-title', __('Tedarikçi Düzenle'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.suppliers.update', $supplier) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Firma Adı')" :value="old('name', $supplier->name)" required /></div>
                <div class="col-md-4"><x-admin-panel::input name="code" :label="__('Kod')" :value="old('code', $supplier->code)" /></div>
                <div class="col-md-6"><x-admin-panel::input name="email" type="email" :label="__('E-posta')" :value="old('email', $supplier->email)" /></div>
                <div class="col-md-6"><x-admin-panel::input name="phone" :label="__('Telefon')" :value="old('phone', $supplier->phone)" /></div>
                <div class="col-md-6"><x-admin-panel::input name="tax_number" :label="__('Vergi No')" :value="old('tax_number', $supplier->tax_number)" /></div>
                <div class="col-md-6"><x-admin-panel::input name="contact_person" :label="__('İletişim Kişisi')" :value="old('contact_person', $supplier->contact_person)" /></div>
                <div class="col-12"><x-admin-panel::input name="address" :label="__('Adres')" :value="old('address', $supplier->address)" /></div>
                <div class="col-md-4">
                    <x-admin-panel::select name="status" :label="__('Durum')"
                        :options="['active' => __('Aktif'), 'inactive' => __('Pasif')]"
                        :selected="old('status', $supplier->status)" />
                </div>
                <div class="col-md-4"><x-admin-panel::input name="credit_limit" type="number" step="0.01" :label="__('Kredi Limiti')" :value="old('credit_limit', $supplier->credit_limit)" /></div>
                <div class="col-md-4"><x-admin-panel::input name="payment_terms_days" type="number" :label="__('Ödeme Vadesi (gün)')" :value="old('payment_terms_days', $supplier->payment_terms_days)" /></div>
            </div>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Güncelle') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.suppliers.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
