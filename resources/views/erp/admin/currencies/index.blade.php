@extends('erp::layouts.app')

@section('title', __('Para Birimleri & Kurlar'))
@section('page-title', __('Para Birimleri & Kurlar'))

@section('content')
    @include('erp::admin.partials.status')

    <div class="row g-3">
        {{-- Sol: Para Birimleri + Kur Tablosu --}}
        <div class="col-md-8">
            <x-admin-panel::card class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">{{ __('Aktif Para Birimleri') }}</h6>
                    <form method="POST" action="{{ route('erp.currencies.fetch-tcmb') }}">
                        @csrf
                        <x-admin-panel::button type="submit" size="sm" variant="outline" icon="refresh-ccw">{{ __('TCMB\'den Güncelle') }}</x-admin-panel::button>
                    </form>
                </div>
                <x-admin-panel::table :headers="[__('Kod'), __('Ad'), __('Sembol'), __('Bugünkü Kur (TRY)'), '']">
                    <tr>
                        <td class="font-monospace fw-bold">{{ config('erp.currency','TRY') }}</td>
                        <td>{{ __('Türk Lirası') }}</td>
                        <td>₺</td>
                        <td><x-admin-panel::badge variant="primary">{{ __('Fonksiyonel') }}</x-admin-panel::badge></td>
                        <td></td>
                    </tr>
                    @forelse($currencies as $currency)
                        <tr>
                            <td class="font-monospace fw-medium">{{ $currency->code }}</td>
                            <td>{{ $currency->name }}</td>
                            <td>{{ $currency->symbol }}</td>
                            <td>
                                @if($latestRates->has($currency->code))
                                    {{ number_format((float) $latestRates[$currency->code]->rate, 4, ',', '.') }} ₺
                                    <span class="text-muted small">({{ $erpFormat->date($latestRates[$currency->code]->rate_date) }})</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <x-admin-panel::badge variant="{{ $currency->is_active ? 'success' : 'secondary' }}">
                                    {{ $currency->is_active ? __('Aktif') : __('Pasif') }}
                                </x-admin-panel::badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Para birimi eklenmemiş.') }}</td></tr>
                    @endforelse
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>

        {{-- Sağ: Yeni Para Birimi + Manuel Kur --}}
        <div class="col-md-4">
            <x-admin-panel::card class="mb-3">
                <h6 class="fw-semibold mb-3">{{ __('Para Birimi Ekle') }}</h6>
                <form method="POST" action="{{ route('erp.currencies.store') }}">
                    @csrf
                    <x-admin-panel::input name="code"   :label="__('Kod (3 harf)')"  placeholder="USD" :value="old('code')" />
                    <x-admin-panel::input name="name"   :label="__('Ad')"             placeholder="{{ __('US Dolar') }}" :value="old('name')" />
                    <x-admin-panel::input name="symbol" :label="__('Sembol')"         placeholder="$" :value="old('symbol')" />
                    <x-admin-panel::button type="submit" variant="primary" icon="plus" class="w-100 mt-2">{{ __('Ekle') }}</x-admin-panel::button>
                </form>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Manuel Kur Gir') }}</h6>
                <form method="POST" action="{{ route('erp.currencies.store-rate') }}">
                    @csrf
                    <x-admin-panel::select name="from_currency" :label="__('Döviz')"
                        :options="$currencies->pluck('code','code')->toArray()"
                        :selected="old('from_currency')" />
                    <x-admin-panel::input name="rate" :label="__('1 Döviz = ? TRY')" type="number" step="0.0001" min="0.0001" :value="old('rate')" />
                    <x-admin-panel::input name="rate_date" :label="__('Tarih')" type="date" :value="old('rate_date', today()->format('Y-m-d'))" />
                    <x-admin-panel::button type="submit" variant="outline" icon="save" class="w-100 mt-2">{{ __('Kaydet') }}</x-admin-panel::button>
                </form>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
