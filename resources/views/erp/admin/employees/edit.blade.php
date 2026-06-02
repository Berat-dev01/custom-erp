@extends('erp::layouts.app')

@section('title', __('Çalışan Düzenle') . ' — ' . $employee->full_name)
@section('page-title', __('Çalışan Düzenle'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.employees.update', $employee) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::input name="first_name" :label="__('Ad')" :value="old('first_name', $employee->first_name)" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="last_name" :label="__('Soyad')" :value="old('last_name', $employee->last_name)" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="email" type="email" :label="__('E-posta')" :value="old('email', $employee->email)" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="phone" :label="__('Telefon')" :value="old('phone', $employee->phone)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="national_id" :label="__('TC Kimlik No')" :value="old('national_id', $employee->national_id)" maxlength="11" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="birth_date" type="date" :label="__('Doğum Tarihi')" :value="old('birth_date', $employee->birth_date?->format('Y-m-d'))" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="gender" :label="__('Cinsiyet')"
                        :options="['' => __('Seçiniz'), 'male' => __('Erkek'), 'female' => __('Kadın'), 'other' => __('Diğer')]"
                        :selected="old('gender', $employee->gender)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="hire_date" type="date" :label="__('İşe Giriş Tarihi')" :value="old('hire_date', $employee->hire_date?->format('Y-m-d'))" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="termination_date" type="date" :label="__('Ayrılış Tarihi')" :value="old('termination_date', $employee->termination_date?->format('Y-m-d'))" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="employment_type" :label="__('İstihdam Türü')" required
                        :options="['full_time' => __('Tam Zamanlı'), 'part_time' => __('Yarı Zamanlı'), 'contract' => __('Sözleşmeli'), 'intern' => __('Stajyer')]"
                        :selected="old('employment_type', $employee->employment_type)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="status" :label="__('Durum')" required
                        :options="['active' => __('Aktif'), 'on_leave' => __('İzinde'), 'terminated' => __('Ayrılmış')]"
                        :selected="old('status', $employee->status)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="department_id" :label="__('Departman')"
                        :options="$departments->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('department_id', $employee->department_id)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="position_id" :label="__('Pozisyon')"
                        :options="$positions->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('position_id', $employee->position_id)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="manager_id" :label="__('Yönetici')"
                        :options="$managers->map(fn($e) => ['id' => $e->id, 'name' => $e->full_name])->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('manager_id', $employee->manager_id)" />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="notes" :label="__('Notlar')" rows="3">{{ old('notes', $employee->notes) }}</x-admin-panel::textarea>
                </div>
            </div>

            @if($errors->any())
                <div class="mt-3">
                    <x-admin-panel::alert type="error">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </x-admin-panel::alert>
                </div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Güncelle') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.employees.show', $employee) }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
