@extends('erp::layouts.app')
@section('title', $position->exists ? __('Pozisyon Düzenle') : __('Yeni Pozisyon'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-positions">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Pozisyonlar</p><h1>{{ $position->exists ? __('Pozisyon Düzenle') : __('Yeni Pozisyon') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.positions.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $position->exists ? route('erp.positions.update', $position) : route('erp.positions.store') }}">
            @csrf
            @if($position->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="title" :label="__('Pozisyon Adı')" :value="old('title', $position->title)" required /></div>
                    <div class="col-md-4">
                        <x-admin-panel::select name="department_id" :label="__('Departman')" :options="$departments" :selected="old('department_id', $position->department_id)" placeholder="Departman seçin" />
                    </div>
                    <div class="col-12"><x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description', $position->description)" /></div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $position->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.positions.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
