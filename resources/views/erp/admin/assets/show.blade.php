@extends('erp::layouts.app')

@section('title', $asset->name)
@section('page-title', $asset->name)

@section('content')
    @include('erp::admin.partials.status')
    @include('erp::admin.partials.status')

    <div class="d-flex gap-2 mb-3 flex-wrap">
        @can('erp.assets.update')
            <x-admin-panel::button href="{{ route('erp.assets.edit', $asset) }}" icon="pencil" variant="outline">{{ __('Düzenle') }}</x-admin-panel::button>
            @if($asset->status === 'active')
                <form method="POST" action="{{ route('erp.assets.depreciate', $asset) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="outline" icon="trending-down">{{ __('Bu Ay Amortisman İşle') }}</x-admin-panel::button>
                </form>
            @endif
        @endcan
        <x-admin-panel::button href="{{ route('erp.assets.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Varlık Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Kod') }}</th><td class="font-monospace">{{ $asset->asset_code }}</td></tr>
                    <tr><th>{{ __('Seri No') }}</th><td>{{ $asset->serial_number ?? '-' }}</td></tr>
                    <tr><th>{{ __('Kategori') }}</th><td>{{ $asset->category?->name }}</td></tr>
                    <tr><th>{{ __('Satın Alma Tarihi') }}</th><td>{{ $erpFormat->date($asset->purchase_date) }}</td></tr>
                    <tr><th>{{ __('Satın Alma Fiyatı') }}</th><td>{{ $erpFormat->money($asset->purchase_price) }}</td></tr>
                    <tr><th>{{ __('Güncel Defter Değeri') }}</th><td class="fw-semibold">{{ $erpFormat->money($asset->current_value) }}</td></tr>
                    <tr><th>{{ __('Toplam Amortisman') }}</th><td class="text-danger">{{ $erpFormat->money($asset->totalDepreciated()) }}</td></tr>
                    <tr><th>{{ __('Aylık Amortisman') }}</th><td>{{ $erpFormat->money($asset->monthlyDepreciationAmount()) }}</td></tr>
                    <tr><th>{{ __('Atanan') }}</th><td>{{ $asset->assignedTo?->full_name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Konum') }}</th><td>{{ $asset->location?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ match($asset->status) { 'active' => 'success', 'in_repair' => 'warning', 'disposed' => 'danger', default => 'secondary' } }}">
                            {{ __($asset->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                    @if($asset->disposal_date)
                        <tr><th>{{ __('Elden Çıkarma') }}</th><td>{{ $erpFormat->date($asset->disposal_date) }}</td></tr>
                    @endif
                </table>
                @if($asset->notes)
                    <p class="text-muted small mt-2">{{ $asset->notes }}</p>
                @endif
            </x-admin-panel::card>
        </div>

        <div class="col-md-7">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Amortisman Geçmişi') }}</h6>
                @if($depreciationHistory->isNotEmpty())
                    <x-admin-panel::table :headers="[__('Dönem'), __('Tutar'), __('Defter Değeri (Sonrası)')]">
                        @foreach($depreciationHistory as $entry)
                            @php
                                $months = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
                            @endphp
                            <tr>
                                <td>{{ ($months[$entry->month] ?? $entry->month) . ' ' . $entry->year }}</td>
                                <td class="text-danger">-{{ $erpFormat->money($entry->amount) }}</td>
                                <td>{{ $erpFormat->money($entry->book_value_after) }}</td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                @else
                    <p class="text-muted">{{ __('Henüz amortisman kaydı yok.') }}</p>
                @endif
            </x-admin-panel::card>
        </div>
    </div>
@endsection
