@extends('erp::layouts.app')
@section('title', __('Malzeme Listeleri (BOM)'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Ürün')], ['label' => __('SKU')], ['label' => __('Versiyon')],
            ['label' => __('Bileşen Sayısı')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-boms">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Malzeme Listeleri (BOM)') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.manufacturing.manage')<x-admin-panel::button :href="route('erp.boms.create')" icon="plus">{{ __('Yeni BOM') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-boms-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.boms.index')" :reset-url="route('erp.boms.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Ürün adı, SKU..." />
                    <x-admin-panel::select name="is_active" label="Durum" :options="['1'=>__('Aktif'),'0'=>__('Pasif')]" :selected="$filters['is_active']" placeholder="Tümü" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'version'=>__('Versiyon')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-boms-bulk" method="POST" action="{{ route('erp.boms.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-boms-bulk" checkbox-selector=".erp-bom-selector" label="BOM">
                    @can('erp.manufacturing.manage')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili BOM kayıtlarını silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Malzeme Listeleri') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($boms as $bom)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $bom->id }}" class="form-check-input erp-bom-selector"></td>
                            <td><a href="{{ route('erp.boms.show', $bom) }}" class="fw-medium">{{ $bom->product?->name ?? '-' }}</a></td>
                            <td><span class="font-monospace small">{{ $bom->product?->sku }}</span></td>
                            <td>{{ $bom->version }}</td>
                            <td>{{ $bom->components_count }}</td>
                            <td><x-admin-panel::badge variant="{{ $bom->is_active ? 'success' : 'secondary' }}">{{ $bom->is_active ? __('Aktif') : __('Pasif') }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.boms.show', $bom)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.manufacturing.manage')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-bom-delete-{{ $bom->id }}" data-admin-confirm="{{ __('Bu BOM kaydını silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('BOM bulunamadı.')])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <x-admin-panel::pagination :paginator="$boms" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($boms as $bom)
            @can('erp.manufacturing.manage')
                <form id="erp-bom-delete-{{ $bom->id }}" method="POST" action="{{ route('erp.boms.destroy', $bom) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
