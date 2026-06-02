@extends('erp::layouts.app')

@section('title', __('Alacak Yaşlandırma Raporu'))
@section('page-title', __('Alacak Yaşlandırma Raporu'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.reports.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Raporlara Dön') }}
        </x-admin-panel::button>
    </div>

    {{-- Özet Kartlar --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Vadesi Gelmemiş')"
                :value="$erpFormat->money($bucket_totals['current'])"
                icon="clock"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('0-30 Gün')"
                :value="$erpFormat->money($bucket_totals['days_30'])"
                icon="alert-circle"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('31-90 Gün')"
                :value="$erpFormat->money($bucket_totals['days_60'] + $bucket_totals['days_90'])"
                icon="alert-triangle"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('90+ Gün')"
                :value="$erpFormat->money($bucket_totals['days_90p'])"
                icon="x-circle"
            />
        </div>
    </div>

    {{-- Özet Tablo --}}
    <x-admin-panel::card class="mb-4">
        <h6 class="fw-semibold mb-3">{{ __('Yaşlandırma Özeti') }}</h6>
        <x-admin-panel::table :headers="[__('Yaş Grubu'), __('Fatura Sayısı'), __('Toplam Kalan'), __('Pay')]">
            @php
                $groups = [
                    'current'  => __('Vadesi Gelmemiş'),
                    'days_30'  => __('0-30 Gün'),
                    'days_60'  => __('31-60 Gün'),
                    'days_90'  => __('61-90 Gün'),
                    'days_90p' => __('90+ Gün'),
                ];
            @endphp
            @foreach($groups as $key => $label)
                @php
                    $total = $bucket_totals[$key];
                    $count = $buckets[$key]->count();
                    $pct   = $grand_total > 0 ? round($total / $grand_total * 100, 1) : 0;
                    $variant = match($key) {
                        'current' => 'info',
                        'days_30' => 'warning',
                        'days_60' => 'warning',
                        'days_90' => 'danger',
                        'days_90p' => 'danger',
                        default    => 'secondary',
                    };
                @endphp
                <tr>
                    <td><x-admin-panel::badge :variant="$variant">{{ $label }}</x-admin-panel::badge></td>
                    <td>{{ $count }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($total) }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1;background:#e9ecef;border-radius:4px;height:6px;">
                                <div style="width:{{ $pct }}%;background:var(--color-primary-500,#4f46e5);border-radius:4px;height:6px;"></div>
                            </div>
                            <span class="text-muted small" style="min-width:40px;">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            <tr class="fw-bold border-top">
                <td>{{ __('Toplam') }}</td>
                <td>{{ $outstanding->count() }}</td>
                <td>{{ $erpFormat->money($grand_total) }}</td>
                <td>100%</td>
            </tr>
        </x-admin-panel::table>
    </x-admin-panel::card>

    {{-- Detaylı Liste --}}
    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3">{{ __('Açık Fatura Detayı') }}</h6>
        <x-admin-panel::table :headers="[__('Fatura No'), __('Vade Tarihi'), __('Gecikme (Gün)'), __('Toplam'), __('Ödenen'), __('Kalan'), __('Durum')]">
            @forelse($outstanding as $inv)
                @php
                    $daysOver = max(0, (int) $inv->days_overdue);
                    $bucket = match(true) {
                        $inv->days_overdue < 0 => 'info',
                        $daysOver <= 30         => 'warning',
                        $daysOver <= 60         => 'warning',
                        $daysOver <= 90         => 'danger',
                        default                 => 'danger',
                    };
                @endphp
                <tr>
                    <td>
                        @can('erp.invoices.view')
                            <a href="{{ route('erp.invoices.show', $inv) }}" class="fw-medium font-monospace">{{ $inv->invoice_number }}</a>
                        @else
                            <span class="fw-medium font-monospace">{{ $inv->invoice_number }}</span>
                        @endcan
                    </td>
                    <td>{{ $erpFormat->date($inv->due_date) }}</td>
                    <td>
                        @if($inv->days_overdue < 0)
                            <span class="text-muted">{{ __('Vadesi gelmemiş') }}</span>
                        @else
                            <x-admin-panel::badge :variant="$bucket">{{ $daysOver }} {{ __('gün') }}</x-admin-panel::badge>
                        @endif
                    </td>
                    <td>{{ $erpFormat->money($inv->total, $inv->currency) }}</td>
                    <td>{{ $erpFormat->money($inv->paid_amount, $inv->currency) }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($inv->remaining, $inv->currency) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($inv->status) { 'overdue' => 'danger', 'partial' => 'warning', default => 'info' } }}">
                            {{ __($inv->status) }}
                        </x-admin-panel::badge>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Açık alacak bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
