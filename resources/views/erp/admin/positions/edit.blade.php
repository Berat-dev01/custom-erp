@extends('erp::layouts.app')

@section('title', __('Pozisyon Düzenle'))
@section('page-title', __('Pozisyon Düzenle'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.positions.update', $position) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::input name="name" :label="__('Pozisyon Adı')" :value="old('name', $position->name)" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="department_id" :label="__('Departman')" required
                        :options="$departments->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('department_id', $position->department_id)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="level" :label="__('Seviye')" required
                        :options="['intern' => __('Stajyer'), 'junior' => __('Junior'), 'mid' => __('Mid'), 'senior' => __('Senior'), 'lead' => __('Lead'), 'manager' => __('Manager'), 'director' => __('Direktör'), 'executive' => __('Üst Yönetim')]"
                        :selected="old('level', $position->level)" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Durum') }}</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                            {{ old('is_active', $position->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Aktif') }}</label>
                    </div>
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
                <x-admin-panel::button href="{{ route('erp.positions.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
