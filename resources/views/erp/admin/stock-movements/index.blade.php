@extends('erp::layouts.app')
@section('title', __('Stok Hareketleri'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => __('Ürün')], ['label' => __('Depo')], ['label' => __('Tip')],
            ['label' => __('Miktar')], ['label' => __('Tarih')], ['label' => __('Referans')],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-stock-movements">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Stok Hareketleri') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.stock_movements.create')<x-admin-panel::button :href="route('erp.stock-movements.create')" icon="plus">{{ __('Yeni Hareket') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-stock-movements-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.stock-movements.index')" :reset-url="route('erp.stock-movements.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::select name="type" label="Tip" :options="['in'=>__('Giriş'),'out'=>__('Çıkış'),'transfer'=>__('Transfer'),'adjustment'=>__('Düzeltme')]" :selected="$filters['type']" placeholder="Tüm tipler" />
                    <x-admin-panel::select name="product_id" label="Ürün" :options="$products" :selected="$filters['product_id']" placeholder="Tüm ürünler" />
                    <x-admin-panel::select name="warehouse_id" label="Depo" :options="$warehouses" :selected="$filters['warehouse_id']" placeholder="Tüm depolar" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'quantity'=>__('Miktar')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <x-admin-panel::card>
                <x-slot:header>{{ __('Stok Hareketleri') }}</x-slot:header>
                <x-admin-panel::table :headers="$tableHeaders">
                @forelse($movements as $mov)
                    <tr>
                        <td>{{ $mov->product?->name ?? '-' }}<div class="crm-muted">{{ $mov->product?->sku }}</div></td>
                        <td>{{ $mov->warehouse?->name ?? '-' }}</td>
                        <td><x-admin-panel::badge variant="{{ $mov->type === 'in' ? 'success' : ($mov->type === 'out' ? 'danger' : 'info') }}">{{ $mov->type }}</x-admin-panel::badge></td>
                        <td>{{ $mov->quantity }}</td>
                        <td>{{ $mov->created_at?->format('d.m.Y H:i') }}</td>
                        <td>{{ $mov->reference ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('erp::admin.partials.empty-state',['title'=>__('Stok hareketi bulunamadı.')])</td></tr>
                @endforelse
                </x-admin-panel::table>
                <x-admin-panel::pagination :paginator="$movements" class="crm-pagination" />
            </x-admin-panel::card>
        </div>
    </section>
@endsection
