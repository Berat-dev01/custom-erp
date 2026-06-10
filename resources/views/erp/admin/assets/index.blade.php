@extends('erp::layouts.app')
@section('title', __('Varlıklar'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Kod')], ['label' => __('Ad')], ['label' => __('Kategori')],
            ['label' => __('Güncel Değer')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-assets">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Varlıklar') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.assets.export')<x-admin-panel::export-button :url="route('erp.assets.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-assets" />@endcan
                @can('erp.assets.create')<x-admin-panel::button :href="route('erp.assets.create')" icon="plus">{{ __('Yeni Varlık') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-assets-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.assets.index')" :reset-url="route('erp.assets.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Ad, kod..." />
                    <x-admin-panel::select name="category_id" label="Kategori" :options="$categories" :selected="$filters['category_id']" placeholder="Tüm kategoriler" />
                    <x-admin-panel::select name="status" label="Durum" :options="['active'=>__('Aktif'),'in_repair'=>__('Tamirde'),'disposed'=>__('Elden çıkarıldı')]" :selected="$filters['status']" placeholder="Tümü" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'name'=>__('Ad'),'purchase_date'=>__('Satın alma'),'current_value'=>__('Güncel değer')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-assets-bulk" method="POST" action="{{ route('erp.assets.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-assets-bulk" checkbox-selector=".erp-asset-selector" label="varlık">
                    @can('erp.assets.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili varlıkları silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Varlıklar') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($assets as $asset)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $asset->id }}" class="form-check-input erp-asset-selector"></td>
                            <td><span class="font-monospace small">{{ $asset->asset_code }}</span></td>
                            <td><a href="{{ route('erp.assets.show', $asset) }}" class="fw-medium">{{ $asset->name }}</a></td>
                            <td>{{ $asset->category?->name ?? '-' }}</td>
                            <td>{{ $erpFormat->money($asset->current_value) }}</td>
                            <td><x-admin-panel::badge variant="{{ $asset->status === 'active' ? 'success' : ($asset->status === 'in_repair' ? 'warning' : 'secondary') }}">{{ $asset->status }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.assets.show', $asset)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.assets.update')<x-admin-panel::button :href="route('erp.assets.edit', $asset)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.assets.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-asset-delete-{{ $asset->id }}" data-admin-confirm="{{ __('Bu varlığı silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Varlık bulunamadı.'),'actionUrl'=>route('erp.assets.create'),'actionLabel'=>__('Yeni Varlık'),'actionPermission'=>'erp.assets.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $assets->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$assets" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($assets as $asset)
            @can('erp.assets.delete')
                <form id="erp-asset-delete-{{ $asset->id }}" method="POST" action="{{ route('erp.assets.destroy', $asset) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
