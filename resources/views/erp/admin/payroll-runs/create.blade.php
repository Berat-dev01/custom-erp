@extends('erp::layouts.app')

@section('title', __('Bordro Çalıştır'))
@section('page-title', __('Bordro Çalıştır'))

@section('content')
    <x-admin-panel::card>
        <p class="text-muted mb-4">{{ __('Seçilen ay için tüm aktif çalışanların bordrolarını hesaplar. Mevcut bordro varsa yeniden hesaplanır.') }}</p>
        <form method="POST" action="{{ route('erp.payroll-runs.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <x-admin-panel::input name="year" type="number" :label="__('Yıl')" :value="old('year', date('Y'))" required min="2020" max="2099" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::select name="month" :label="__('Ay')" required
                        :options="[1=>__('Ocak'),2=>__('Şubat'),3=>__('Mart'),4=>__('Nisan'),5=>__('Mayıs'),6=>__('Haziran'),7=>__('Temmuz'),8=>__('Ağustos'),9=>__('Eylül'),10=>__('Ekim'),11=>__('Kasım'),12=>__('Aralık')]"
                        :selected="old('month', (int)date('m'))" />
                </div>
            </div>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="play">{{ __('Bordroyı Hesapla') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.payroll-runs.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
