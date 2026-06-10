@extends('erp::layouts.app')
@section('title', $expense->exists ? __('Gider Düzenle') : __('Yeni Gider'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-expenses">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Giderler</p><h1>{{ $expense->exists ? __('Gider Düzenle') : __('Yeni Gider') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.expenses.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $expense->exists ? route('erp.expenses.update', $expense) : route('erp.expenses.store') }}" enctype="multipart/form-data">
            @csrf
            @if($expense->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-12"><x-admin-panel::input name="title" :label="__('Başlık')" :value="old('title', $expense->title)" required /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="category" :label="__('Kategori')" :options="['office'=>__('Ofis'),'travel'=>__('Seyahat'),'utilities'=>__('Faturalar'),'salary'=>__('Maaş'),'rent'=>__('Kira'),'marketing'=>__('Pazarlama'),'other'=>__('Diğer')]" :selected="old('category', $expense->category)" required />
                    </div>
                    <div class="col-md-6"><x-admin-panel::input name="amount" type="number" step="0.01" :label="__('Tutar')" :value="old('amount', $expense->amount)" required /></div>
                    <div class="col-md-6"><x-admin-panel::input name="expense_date" type="date" :label="__('Tarih')" :value="old('expense_date', $expense->expense_date?->format('Y-m-d') ?? date('Y-m-d'))" required /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="payment_method" :label="__('Ödeme Yöntemi')" :options="['cash'=>__('Nakit'),'bank_transfer'=>__('Banka havalesi'),'credit_card'=>__('Kredi kartı'),'other'=>__('Diğer')]" :selected="old('payment_method', $expense->payment_method ?? 'cash')" />
                    </div>
                    <div class="col-12"><label class="form-label">{{ __('Fiş / Makbuz') }}</label><input type="file" name="receipt" class="form-control" accept="image/*,.pdf"></div>
                    <div class="col-12"><x-admin-panel::input name="notes" :label="__('Notlar')" :value="old('notes', $expense->notes)" /></div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $expense->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.expenses.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
