@extends('erp::layouts.app')
@section('title', $project->exists ? __('Proje Düzenle') : __('Yeni Proje'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-projects">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Projeler</p><h1>{{ $project->exists ? __('Proje Düzenle') : __('Yeni Proje') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.projects.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $project->exists ? route('erp.projects.update', $project) : route('erp.projects.store') }}">
            @csrf
            @if($project->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Proje Adı')" :value="old('name', $project->name)" required /></div>
                    <div class="col-md-4">
                        <x-admin-panel::select name="status" :label="__('Durum')" :options="['planning'=>__('Planlama'),'active'=>__('Aktif'),'on_hold'=>__('Beklemede'),'completed'=>__('Tamamlandı'),'cancelled'=>__('İptal')]" :selected="old('status', $project->status ?? 'planning')" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="customer_id" :label="__('Müşteri')" :options="$customers" :selected="old('customer_id', $project->customer_id)" placeholder="Müşteri seçin" />
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" data-admin-select data-admin-select-placeholder="{{ __('Proje yöneticisi seçin') }}" data-admin-select-searchable="1" data-admin-select-clearable="1">
                            <label class="form-label">{{ __('Proje Yöneticisi') }}</label>
                            <select name="manager_id" class="form-control" data-admin-select-native>
                                <option value=""></option>
                                @foreach($managers as $m)
                                    <option value="{{ $m->id }}" {{ old('manager_id', $project->manager_id) == $m->id ? 'selected' : '' }}>{{ $m->last_name }} {{ $m->first_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4"><x-admin-panel::input name="start_date" type="date" :label="__('Başlangıç Tarihi')" :value="old('start_date', $project->start_date?->format('Y-m-d'))" /></div>
                    <div class="col-md-4"><x-admin-panel::input name="end_date" type="date" :label="__('Bitiş Tarihi')" :value="old('end_date', $project->end_date?->format('Y-m-d'))" /></div>
                    <div class="col-md-4"><x-admin-panel::input name="budget" type="number" step="0.01" :label="__('Bütçe')" :value="old('budget', $project->budget)" /></div>
                    <div class="col-12"><x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description', $project->description)" /></div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $project->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.projects.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
