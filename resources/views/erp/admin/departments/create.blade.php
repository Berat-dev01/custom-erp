@extends('erp::layouts.app')

@section('title', __('Yeni Departman'))
@section('page-title', __('Yeni Departman'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.departments.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::input name="name" :label="__('Departman Adı')" :value="old('name')" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="code" :label="__('Kod')" :value="old('code')" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="parent_id" :label="__('Üst Departman')"
                        :options="$parents->pluck('name','id')->prepend(__('Yok'), '')->toArray()"
                        :selected="old('parent_id')" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="manager_id" :label="__('Departman Yöneticisi')"
                        :options="$managers->map(fn($e) => ['id' => $e->id, 'name' => $e->full_name])->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('manager_id')" />
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
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.departments.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
