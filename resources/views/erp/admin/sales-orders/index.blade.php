@extends('erp::layouts.app')
@section('title', __('Satış Siparişleri'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Sipariş No')], ['label' => __('Müşteri')], ['label' => __('Tarih')],
            ['label' => __('Toplam')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-sales-orders">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Satış Siparişleri') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.sales-orders.export')<x-admin-panel::export-button :url="route('erp.sales-orders.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-sales-orders" />@endcan
                @can('erp.sales-orders.create')<x-admin-panel::button :href="route('erp.sales-orders.create')" icon="plus">{{ __('Yeni Sipariş') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-sales-orders-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.sales-orders.index')" :reset-url="route('erp.sales-orders.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Sipariş no, müşteri..." />
                    <x-admin-panel::select name="status" label="Durum" :options="['draft'=>__('Taslak'),'confirmed'=>__('Onaylandı'),'picking'=>__('Hazırlanıyor'),'shipped'=>__('Kargoya verildi'),'delivered'=>__('Teslim edildi'),'cancelled'=>__('İptal')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                    <x-admin-panel::select name="customer_id" label="Müşteri" :options="$customers" :selected="$filters['customer_id']" placeholder="Tüm müşteriler" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'so_number'=>__('Sipariş no'),'order_date'=>__('Tarih'),'total'=>__('Toplam')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-so-bulk" method="POST" action="{{ route('erp.sales-orders.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-so-bulk" checkbox-selector=".erp-so-selector" label="sipariş">
                    @can('erp.sales-orders.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili siparişleri silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Satış Siparişleri') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($orders as $order)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $order->id }}" class="form-check-input erp-so-selector"></td>
                            <td><a href="{{ route('erp.sales-orders.show', $order) }}" class="fw-medium font-monospace">{{ $order->so_number }}</a></td>
                            <td>{{ $order->customer?->name ?? '-' }}</td>
                            <td>{{ $order->order_date?->format('d.m.Y') }}</td>
                            <td>{{ $erpFormat->money($order->total) }}</td>
                            <td><x-admin-panel::badge variant="{{ match($order->status) { 'delivered'=>'success','confirmed'=>'info','shipped'=>'warning',default=>'secondary' } }}">{{ $order->status }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.sales-orders.show', $order)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.sales-orders.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-so-delete-{{ $order->id }}" data-admin-confirm="{{ __('Bu siparişi silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Satış siparişi bulunamadı.'),'actionUrl'=>route('erp.sales-orders.create'),'actionLabel'=>__('Yeni Sipariş'),'actionPermission'=>'erp.sales-orders.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $orders->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$orders" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($orders as $order)
            @can('erp.sales-orders.delete')
                <form id="erp-so-delete-{{ $order->id }}" method="POST" action="{{ route('erp.sales-orders.destroy', $order) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
