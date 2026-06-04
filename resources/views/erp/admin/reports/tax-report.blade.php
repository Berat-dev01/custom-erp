@extends('erp::layouts.app')

@section('title', __('KDV Raporu'))
@section('page-title', __('KDV Raporu'))

@section('content')
    <form method="GET" class="d-flex gap-2 mb-4 align-items-end flex-wrap">
        <div>
            <label class="form-label small">{{ __('Yıl') }}</label>
            <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" min="2020" max="2030" style="width:100px" />
        </div>
        <x-admin-panel::select name="month"
            :options="collect(range(1,12))->mapWithKeys(fn($m) => [$m => \Carbon\Carbon::create(null,$m,1)->translatedFormat('F')])->toArray()"
            :selected="$month" />
        <div class="pb-1"><x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Uygula') }}</x-admin-panel::button></div>
    </form>

    <h6 class="fw-semibold mb-3">
        {{ __('KDV Raporu') }} — {{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}
    </h6>

    <div class="row g-3">
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('İndirilecek KDV (Alış)')" :value="$erpFormat->money($data['inbound_vat'])" icon="arrow-down-circle" />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('Hesaplanan KDV (Satış)')" :value="$erpFormat->money($data['outbound_vat'])" icon="arrow-up-circle" />
        </div>
        <div class="col-sm-4">
            @if($data['payable_vat'] >= 0)
                <x-admin-panel::stat-card :label="__('Ödenecek KDV')" :value="$erpFormat->money($data['payable_vat'])" icon="credit-card" :trend="__('Borç')" trend-direction="down" />
            @else
                <x-admin-panel::stat-card :label="__('İade Edilecek KDV')" :value="$erpFormat->money($data['refundable_vat'])" icon="refresh-ccw" :trend="__('Alacak')" trend-direction="up" />
            @endif
        </div>
    </div>

    <x-admin-panel::card class="mt-4">
        <h6 class="fw-semibold mb-3">{{ __('KDV Özeti') }}</h6>
        <table class="table table-sm">
            <tr><td>{{ __('Hesaplanan KDV (391)') }}</td><td class="text-end fw-medium">{{ $erpFormat->money($data['outbound_vat']) }}</td></tr>
            <tr><td>(-) {{ __('İndirilecek KDV (191)') }}</td><td class="text-end fw-medium">{{ $erpFormat->money($data['inbound_vat']) }}</td></tr>
            <tr class="fw-bold border-top">
                <td>{{ $data['payable_vat'] >= 0 ? __('Ödenecek KDV') : __('Devreden KDV') }}</td>
                <td class="text-end {{ $data['payable_vat'] >= 0 ? 'text-danger' : 'text-success' }}">
                    {{ $erpFormat->money(abs($data['payable_vat'])) }}
                </td>
            </tr>
        </table>
        <p class="text-muted small mt-2 mb-0">
            {{ __('Not: Bu rapor yalnızca muhasebeye işlenmiş (posted) fişleri içerir. Gerçek KDV beyannamesi için yasal danışmanlık alınız.') }}
        </p>
    </x-admin-panel::card>
@endsection
