@extends('erp::layouts.app')
@section('title', $employee->exists ? __('Çalışan Düzenle') : __('Yeni Çalışan'))
@section('content')
    <section class="crm-admin-page" data-crm-module="erp-employees">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP / Çalışanlar</p><h1>{{ $employee->exists ? __('Çalışan Düzenle') : __('Yeni Çalışan') }}</h1></div>
            <div class="crm-admin-actions"><x-admin-panel::button :href="route('erp.employees.index')" variant="ghost" icon="arrow-left">{{ __('Geri') }}</x-admin-panel::button></div>
        </header>
        <form method="POST" action="{{ $employee->exists ? route('erp.employees.update', $employee) : route('erp.employees.store') }}">
            @csrf
            @if($employee->exists) @method('PUT') @endif
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-md-4"><x-admin-panel::input name="first_name" :label="__('Ad')" :value="old('first_name', $employee->first_name)" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="last_name" :label="__('Soyad')" :value="old('last_name', $employee->last_name)" required /></div>
                    <div class="col-md-4"><x-admin-panel::input name="employee_number" :label="__('Sicil No')" :value="old('employee_number', $employee->employee_number)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="email" type="email" :label="__('E-posta')" :value="old('email', $employee->email)" /></div>
                    <div class="col-md-6"><x-admin-panel::input name="phone" :label="__('Telefon')" :value="old('phone', $employee->phone)" /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="department_id" :label="__('Departman')" :options="$departments" :selected="old('department_id', $employee->department_id)" placeholder="Departman seçin" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="position_id" :label="__('Pozisyon')" :options="$positions" :selected="old('position_id', $employee->position_id)" placeholder="Pozisyon seçin" />
                    </div>
                    <div class="col-md-6"><x-admin-panel::input name="hire_date" type="date" :label="__('İşe Giriş Tarihi')" :value="old('hire_date', $employee->hire_date?->format('Y-m-d'))" /></div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="employment_type" :label="__('Çalışma Tipi')" :options="['full_time'=>__('Tam zamanlı'),'part_time'=>__('Yarı zamanlı'),'contractor'=>__('Sözleşmeli')]" :selected="old('employment_type', $employee->employment_type ?? 'full_time')" />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::select name="status" :label="__('Durum')" :options="['active'=>__('Aktif'),'terminated'=>__('Ayrıldı'),'on_leave'=>__('İzinde')]" :selected="old('status', $employee->status ?? 'active')" />
                    </div>
                </div>
            </x-admin-panel::card>
            <div class="crm-form-actions mt-3">
                <x-admin-panel::button type="submit" icon="save">{{ $employee->exists ? __('Güncelle') : __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('erp.employees.index')" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
