@extends('erp::layouts.app')
@section('title', __('Depolar'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Ad')], ['label' => __('Konum')], ['label' => __('Ürün Çeşidi')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-warehouses">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Depolar') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.warehouses.create')<x-admin-panel::button :href="route('erp.warehouses.create')" icon="plus">{{ __('Yeni Depo') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-warehouses-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.warehouses.index')" :reset-url="route('erp.warehouses.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Depo adı, konum..." />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['name'=>__('Ad'),'location'=>__('Konum'),'created_at'=>__('Eklenme')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-warehouses-bulk" method="POST" action="{{ route('erp.warehouses.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-warehouses-bulk" checkbox-selector=".erp-wh-selector" label="depo">
                    @can('erp.warehouses.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili depoları silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Depolar') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($warehouses as $wh)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $wh->id }}" class="form-check-input erp-wh-selector"></td>
                            <td><a href="{{ route('erp.warehouses.show', $wh) }}" class="fw-medium">{{ $wh->name }}</a>@if($wh->is_default)<span class="badge bg-primary ms-1">{{ __('Varsayılan') }}</span>@endif</td>
                            <td>{{ $wh->location ?? '-' }}</td>
                            <td>{{ $wh->stock_levels_count }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.warehouses.show', $wh)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.warehouses.update')<x-admin-panel::button :href="route('erp.warehouses.edit', $wh)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.warehouses.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-wh-delete-{{ $wh->id }}" data-admin-confirm="{{ __('Bu depoyu silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('erp::admin.partials.empty-state',['title'=>__('Depo bulunamadı.'),'actionUrl'=>route('erp.warehouses.create'),'actionLabel'=>__('Yeni Depo'),'actionPermission'=>'erp.warehouses.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <x-admin-panel::pagination :paginator="$warehouses" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($warehouses as $wh)
            @can('erp.warehouses.delete')
                <form id="erp-wh-delete-{{ $wh->id }}" method="POST" action="{{ route('erp.warehouses.destroy', $wh) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
