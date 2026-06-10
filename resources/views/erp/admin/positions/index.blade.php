@extends('erp::layouts.app')
@section('title', __('Pozisyonlar'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Pozisyon')], ['label' => __('Departman')], ['label' => __('Çalışan Sayısı')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-positions">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Pozisyonlar') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.positions.create')<x-admin-panel::button :href="route('erp.positions.create')" icon="plus">{{ __('Yeni Pozisyon') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-positions-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.positions.index')" :reset-url="route('erp.positions.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Pozisyon adı..." />
                    <x-admin-panel::select name="department_id" label="Departman" :options="$departments" :selected="$filters['department_id']" placeholder="Tüm departmanlar" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['title'=>__('Pozisyon adı'),'created_at'=>__('Eklenme')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-positions-bulk" method="POST" action="{{ route('erp.positions.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-positions-bulk" checkbox-selector=".erp-pos-selector" label="pozisyon">
                    @can('erp.positions.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili pozisyonları silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Pozisyonlar') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($positions as $pos)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $pos->id }}" class="form-check-input erp-pos-selector"></td>
                            <td><strong>{{ $pos->title }}</strong></td>
                            <td>{{ $pos->department?->name ?? '-' }}</td>
                            <td>{{ $pos->employees_count }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    @can('erp.positions.update')<x-admin-panel::button :href="route('erp.positions.edit', $pos)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.positions.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-pos-delete-{{ $pos->id }}" data-admin-confirm="{{ __('Bu pozisyonu silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('erp::admin.partials.empty-state',['title'=>__('Pozisyon bulunamadı.'),'actionUrl'=>route('erp.positions.create'),'actionLabel'=>__('Yeni Pozisyon'),'actionPermission'=>'erp.positions.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <x-admin-panel::pagination :paginator="$positions" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($positions as $pos)
            @can('erp.positions.delete')
                <form id="erp-pos-delete-{{ $pos->id }}" method="POST" action="{{ route('erp.positions.destroy', $pos) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
