@extends('erp::layouts.app')
@section('title', __('Satın Alma Siparişleri'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('PO No')], ['label' => __('Tedarikçi')], ['label' => __('Tarih')],
            ['label' => __('Toplam')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-purchase-orders">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Satın Alma Siparişleri') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.purchase-orders.export')<x-admin-panel::export-button :url="route('erp.purchase-orders.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-purchase-orders" />@endcan
                @can('erp.purchase-orders.create')<x-admin-panel::button :href="route('erp.purchase-orders.create')" icon="plus">{{ __('Yeni PO') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-po-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.purchase-orders.index')" :reset-url="route('erp.purchase-orders.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="PO no, tedarikçi..." />
                    <x-admin-panel::select name="status" label="Durum" :options="['draft'=>__('Taslak'),'sent'=>__('Gönderildi'),'partial'=>__('Kısmi alındı'),'received'=>__('Alındı'),'cancelled'=>__('İptal')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                    <x-admin-panel::select name="supplier_id" label="Tedarikçi" :options="$suppliers" :selected="$filters['supplier_id']" placeholder="Tüm tedarikçiler" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'po_number'=>__('PO no'),'order_date'=>__('Tarih'),'total'=>__('Toplam')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-po-bulk" method="POST" action="{{ route('erp.purchase-orders.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-po-bulk" checkbox-selector=".erp-po-selector" label="sipariş">
                    @can('erp.purchase-orders.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili siparişleri silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Satın Alma Siparişleri') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($orders as $order)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $order->id }}" class="form-check-input erp-po-selector"></td>
                            <td><a href="{{ route('erp.purchase-orders.show', $order) }}" class="fw-medium font-monospace">{{ $order->po_number }}</a></td>
                            <td>{{ $order->supplier?->name ?? '-' }}</td>
                            <td>{{ $order->order_date?->format('d.m.Y') }}</td>
                            <td>{{ $erpFormat->money($order->total) }}</td>
                            <td><x-admin-panel::badge variant="{{ match($order->status) { 'received'=>'success','sent'=>'info','partial'=>'warning',default=>'secondary' } }}">{{ $order->status }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.purchase-orders.show', $order)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.purchase-orders.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-po-delete-{{ $order->id }}" data-admin-confirm="{{ __('Bu siparişi silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Satın alma siparişi bulunamadı.'),'actionUrl'=>route('erp.purchase-orders.create'),'actionLabel'=>__('Yeni PO'),'actionPermission'=>'erp.purchase-orders.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $orders->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$orders" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($orders as $order)
            @can('erp.purchase-orders.delete')
                <form id="erp-po-delete-{{ $order->id }}" method="POST" action="{{ route('erp.purchase-orders.destroy', $order) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
