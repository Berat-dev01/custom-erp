@extends('erp::layouts.app')
@section('title', $department->exists ? __('Departman Düzenle') : __('Yeni Departman'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-departments">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Departmanlar</p><h1>{{ $department->exists ? __('Departman Düzenle') : __('Yeni Departman') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.departments.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $department->exists ? route('erp.departments.update', $department) : route('erp.departments.store') }}">
            @csrf
            @if($department->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Departman Adı')" :value="old('name', $department->name)" required /></div>
                    <div class="col-md-4">
                        <x-admin-panel::select name="parent_id" :label="__('Üst Departman')" :options="$parents->except($department->id ?? null)" :selected="old('parent_id', $department->parent_id)" placeholder="Yok" />
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" data-admin-select data-admin-select-placeholder="{{ __('Müdür seçin') }}" data-admin-select-searchable="1" data-admin-select-clearable="1">
                            <label class="form-label">{{ __('Müdür') }}</label>
                            <select name="manager_id" class="form-control" data-admin-select-native>
                                <option value=""></option>
                                @foreach($managers as $m)
                                    <option value="{{ $m->id }}" {{ old('manager_id', $department->manager_id) == $m->id ? 'selected' : '' }}>{{ $m->last_name }} {{ $m->first_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12"><x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description', $department->description)" /></div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $department->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.departments.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
