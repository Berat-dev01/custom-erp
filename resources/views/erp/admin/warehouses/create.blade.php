@extends('erp::layouts.app')

@section('title', __('Yeni Depo'))
@section('page-title', __('Yeni Depo'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.warehouses.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <x-admin-panel::input name="name" :label="__('Depo Adı')" :value="old('name')" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="code" :label="__('Kod')" :value="old('code')" required />
                </div>
                <div class="col-12">
                    <x-admin-panel::input name="address" :label="__('Adres')" :value="old('address')" />
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">{{ __('Varsayılan Depo') }}</label>
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
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.warehouses.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
