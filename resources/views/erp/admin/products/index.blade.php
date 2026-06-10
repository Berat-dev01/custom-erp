@extends('erp::layouts.app')
@section('title', __('Ürünler'))
@section('page-title', __('Ürünler'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('SKU')], ['label' => __('Ürün Adı')], ['label' => __('Kategori')],
            ['label' => __('Satış Fiyatı')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-products">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Ürünler') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.products.export')
                    <x-admin-panel::export-button :url="route('erp.products.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-products" />
                @endcan
                @can('erp.products.create')
                    <x-admin-panel::button :href="route('erp.products.create')" icon="plus">{{ __('Yeni Ürün') }}</x-admin-panel::button>
                @endcan
            </div>
        </header>
        <div id="erp-products-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.products.index')" :reset-url="route('erp.products.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Ürün adı, SKU..." />
                    <x-admin-panel::select name="category_id" label="Kategori" :options="$categories" :selected="$filters['category_id']" placeholder="Tüm kategoriler" />
                    <x-admin-panel::select name="is_active" label="Durum" :options="['1'=>__('Aktif'),'0'=>__('Pasif')]" :selected="$filters['is_active']" placeholder="Tümü" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'name'=>__('Ad'),'sale_price'=>__('Satış fiyatı')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-products-bulk" method="POST" action="{{ route('erp.products.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-products-bulk" checkbox-selector=".erp-product-selector" label="ürün">
                    @can('erp.products.delete')
                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili ürünleri silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>
                    @endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Ürünler') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($products as $product)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $product->id }}" class="form-check-input erp-product-selector"></td>
                            <td><span class="font-monospace small">{{ $product->sku }}</span></td>
                            <td><a href="{{ route('erp.products.show', $product) }}" class="fw-medium">{{ $product->name }}</a>@if($product->unit)<div class="crm-muted">{{ $product->unit->abbreviation }}</div>@endif</td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td>{{ $erpFormat->money($product->sale_price) }}</td>
                            <td><x-admin-panel::badge variant="{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? __('Aktif') : __('Pasif') }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.products.show', $product)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.products.update')<x-admin-panel::button :href="route('erp.products.edit', $product)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.products.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-product-delete-{{ $product->id }}" data-admin-confirm="{{ __('Bu ürünü silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Ürün bulunamadı.'),'actionUrl'=>route('erp.products.create'),'actionLabel'=>__('Yeni Ürün'),'actionPermission'=>'erp.products.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $products->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$products" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($products as $product)
            @can('erp.products.delete')
                <form id="erp-product-delete-{{ $product->id }}" method="POST" action="{{ route('erp.products.destroy', $product) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
