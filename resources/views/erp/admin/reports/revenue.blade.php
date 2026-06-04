@extends('erp::layouts.app')

@section('title', __('Gelir / Gider Raporu'))
@section('page-title', __('Gelir / Gider Raporu'))

@push('styles')
<style>
.report-summary-card {
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 0;
}
</style>
@endpush

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.reports.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Raporlara Dön') }}
        </x-admin-panel::button>
    </div>

    {{-- Özet Kartlar --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-admin-panel::stat-card
                :label="__('12 Ay Toplam Gelir')"
                :value="$erpFormat->money($total_revenue)"
                icon="trending-up"
            />
        </div>
        <div class="col-md-4">
            <x-admin-panel::stat-card
                :label="__('12 Ay Toplam Gider')"
                :value="$erpFormat->money($total_expense)"
                icon="trending-down"
            />
        </div>
        <div class="col-md-4">
            <x-admin-panel::stat-card
                :label="__('Net Kâr')"
                :value="$erpFormat->money($net_profit)"
                icon="dollar-sign"
                :trend="$net_profit >= 0 ? __('Kâr') : __('Zarar')"
                :trend-direction="$net_profit >= 0 ? 'up' : 'down'"
            />
        </div>
    </div>

    {{-- Grafik --}}
    <x-admin-panel::card class="mb-4">
        <h6 class="fw-semibold mb-3">{{ __('Son 12 Ay Gelir / Gider') }}</h6>
        <div style="position:relative;height:320px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </x-admin-panel::card>

    {{-- Tablo --}}
    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3">{{ __('Aylık Detay') }}</h6>
        <x-admin-panel::table :headers="[__('Ay'), __('Gelir'), __('Gider'), __('Net')]">
            @foreach($labels as $i => $label)
                @php
                    $rev = $revenue_data[$i];
                    $exp = $expense_data[$i];
                    $net = $rev - $exp;
                @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $erpFormat->money($rev) }}</td>
                    <td>{{ $erpFormat->money($exp) }}</td>
                    <td class="{{ $net >= 0 ? 'text-success' : 'text-danger' }} fw-medium">
                        {{ $erpFormat->money($net) }}
                    </td>
                </tr>
            @endforeach
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels   = @json($labels);
    const revenue  = @json($revenue_data);
    const expenses = @json($expense_data);

    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '{{ __('Gelir') }}',
                    data: revenue,
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderColor: 'rgba(22, 163, 74, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: '{{ __('Gider') }}',
                    data: expenses,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: 'rgba(220, 38, 38, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ctx.dataset.label + ': ' +
                                new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2 }).format(ctx.parsed.y) +
                                ' {{ config('erp.currency', 'TRY') }}';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (v) {
                            return new Intl.NumberFormat('tr-TR', { notation: 'compact' }).format(v);
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endpush
