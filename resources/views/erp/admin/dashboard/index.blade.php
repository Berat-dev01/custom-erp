@extends('erp::layouts.app')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))

@section('content')
    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Bu Ay Gelir')"
                :value="$erpFormat->money($revenue_this_month)"
                icon="trending-up"
                :trend="$revenue_trend !== null ? abs($revenue_trend).'%' : null"
                :trend-direction="$revenue_trend >= 0 ? 'up' : 'down'"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Açık Alacaklar')"
                :value="$erpFormat->money($outstanding_invoices)"
                icon="clock"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Vadesi Geçmiş')"
                :value="$erpFormat->money($overdue_invoices)"
                icon="alert-triangle"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Bu Ay Gider')"
                :value="$erpFormat->money($expenses_this_month)"
                icon="receipt"
            />
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Aktif Çalışan')"
                :value="(string) $active_employees"
                icon="users"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Aktif Proje')"
                :value="(string) $active_projects"
                icon="clipboard-list"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Açık Satın Alma')"
                :value="(string) $open_purchase_orders"
                icon="shopping-cart"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin-panel::stat-card
                :label="__('Düşük Stok Ürün')"
                :value="(string) $low_stock_products"
                icon="package"
                :trend="$low_stock_products > 0 ? __('Dikkat') : null"
                :trend-direction="$low_stock_products > 0 ? 'down' : 'up'"
            />
        </div>
    </div>

    {{-- Son Faturalar --}}
    <x-admin-panel::card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-semibold">{{ __('Son Faturalar') }}</h6>
            @can('erp.invoices.view')
                <x-admin-panel::button href="{{ route('erp.invoices.index') }}" size="sm" variant="ghost" icon="arrow-right">
                    {{ __('Tümünü Gör') }}
                </x-admin-panel::button>
            @endcan
        </div>
        <x-admin-panel::table :headers="[__('Fatura No'), __('Tarih'), __('Vade'), __('Tutar'), __('Durum')]">
            @forelse($recent_invoices as $inv)
                <tr>
                    <td>
                        @can('erp.invoices.view')
                            <a href="{{ route('erp.invoices.show', $inv) }}" class="fw-medium font-monospace">{{ $inv->invoice_number }}</a>
                        @else
                            <span class="fw-medium font-monospace">{{ $inv->invoice_number }}</span>
                        @endcan
                    </td>
                    <td>{{ $erpFormat->date($inv->issue_date) }}</td>
                    <td class="{{ $inv->status === 'overdue' ? 'text-danger fw-medium' : '' }}">{{ $erpFormat->date($inv->due_date) }}</td>
                    <td>{{ $erpFormat->money($inv->total, $inv->currency) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($inv->status) { 'paid' => 'success', 'overdue' => 'danger', 'draft' => 'secondary', 'partial' => 'warning', 'cancelled' => 'danger', default => 'info' } }}">
                            {{ __($inv->status) }}
                        </x-admin-panel::badge>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Henüz fatura yok.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
