@extends('erp::layouts.app')

@section('title', __('Gelir Tablosu'))
@section('page-title', __('Gelir Tablosu'))

@section('content')
    <form method="GET" class="d-flex gap-2 mb-4 align-items-end flex-wrap">
        <x-admin-panel::input name="date_from" type="date" :label="__('Başlangıç')" :value="$from->format('Y-m-d')" />
        <x-admin-panel::input name="date_to"   type="date" :label="__('Bitiş')"     :value="$to->format('Y-m-d')" />
        <div class="pb-1"><x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Uygula') }}</x-admin-panel::button></div>
    </form>

    <h6 class="fw-semibold mb-3">{{ __('Gelir Tablosu') }} — {{ $erpFormat->date($from) }} / {{ $erpFormat->date($to) }}</h6>

    <div class="row g-3">
        <div class="col-md-6">
            <x-admin-panel::stat-card :label="__('Toplam Gelir')" :value="$erpFormat->money($data['total_revenue'])" icon="trending-up" />
        </div>
        <div class="col-md-6">
            <x-admin-panel::stat-card :label="__('Net Kâr/Zarar')" :value="$erpFormat->money($data['net_profit'])"
                icon="{{ $data['net_profit'] >= 0 ? 'smile' : 'frown' }}"
                :trend="$data['net_profit'] >= 0 ? __('Kâr') : __('Zarar')"
                :trend-direction="$data['net_profit'] >= 0 ? 'up' : 'down'" />
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-bold text-success mb-3">{{ __('GELİRLER') }}</h6>
                <x-admin-panel::table :headers="[__('Hesap'), __('Tutar')]">
                    @forelse($data['revenue'] as $row)
                        <tr>
                            <td><span class="text-muted small font-monospace me-2">{{ $row['code'] }}</span>{{ $row['name'] }}</td>
                            <td class="text-end text-success">{{ $erpFormat->money($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">{{ __('Gelir hareketi yok.') }}</td></tr>
                    @endforelse
                    <tr class="fw-bold border-top">
                        <td>{{ __('Toplam Gelir') }}</td>
                        <td class="text-end text-success">{{ $erpFormat->money($data['total_revenue']) }}</td>
                    </tr>
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>

        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-bold text-danger mb-3">{{ __('GİDERLER') }}</h6>
                <x-admin-panel::table :headers="[__('Hesap'), __('Tutar')]">
                    @forelse($data['expenses'] as $row)
                        <tr>
                            <td><span class="text-muted small font-monospace me-2">{{ $row['code'] }}</span>{{ $row['name'] }}</td>
                            <td class="text-end text-danger">{{ $erpFormat->money($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">{{ __('Gider hareketi yok.') }}</td></tr>
                    @endforelse
                    <tr class="fw-bold border-top">
                        <td>{{ __('Toplam Gider') }}</td>
                        <td class="text-end text-danger">{{ $erpFormat->money($data['total_expenses']) }}</td>
                    </tr>
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>
    </div>

    <x-admin-panel::card class="mt-3">
        <div class="d-flex justify-content-between fw-bold fs-5">
            <span>{{ __('NET KÂR / ZARAR') }}</span>
            <span class="{{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $erpFormat->money($data['net_profit']) }}</span>
        </div>
    </x-admin-panel::card>
@endsection
