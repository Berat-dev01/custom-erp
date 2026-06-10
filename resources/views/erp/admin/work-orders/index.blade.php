@extends('erp::layouts.app')
@section('title', __('İş Emirleri'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('WO No')], ['label' => __('Ürün')], ['label' => __('Miktar')],
            ['label' => __('Planlanan Başlangıç')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-work-orders">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('İş Emirleri') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.manufacturing.manage')<x-admin-panel::button :href="route('erp.work-orders.create')" icon="plus">{{ __('Yeni İş Emri') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-work-orders-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.work-orders.index')" :reset-url="route('erp.work-orders.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="WO no, ürün..." />
                    <x-admin-panel::select name="status" label="Durum" :options="['draft'=>__('Taslak'),'released'=>__('Serbest bırakıldı'),'in_progress'=>__('Devam ediyor'),'completed'=>__('Tamamlandı'),'cancelled'=>__('İptal')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'wo_number'=>__('WO no'),'planned_start'=>__('Planlanan başlangıç')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-wo-bulk" method="POST" action="{{ route('erp.work-orders.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-wo-bulk" checkbox-selector=".erp-wo-selector" label="iş emri">
                    @can('erp.manufacturing.manage')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili iş emirlerini silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('İş Emirleri') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($orders as $wo)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $wo->id }}" class="form-check-input erp-wo-selector"></td>
                            <td><a href="{{ route('erp.work-orders.show', $wo) }}" class="fw-medium font-monospace">{{ $wo->wo_number }}</a></td>
                            <td>{{ $wo->product?->name ?? '-' }}</td>
                            <td>{{ $wo->planned_quantity }}</td>
                            <td>{{ $wo->planned_start?->format('d.m.Y') }}</td>
                            <td><x-admin-panel::badge variant="{{ match($wo->status) { 'completed'=>'success','in_progress'=>'info','released'=>'warning',default=>'secondary' } }}">{{ $wo->status }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.work-orders.show', $wo)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.manufacturing.manage')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-wo-delete-{{ $wo->id }}" data-admin-confirm="{{ __('Bu iş emrini silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('İş emri bulunamadı.')])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <x-admin-panel::pagination :paginator="$orders" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($orders as $wo)
            @can('erp.manufacturing.manage')
                <form id="erp-wo-delete-{{ $wo->id }}" method="POST" action="{{ route('erp.work-orders.destroy', $wo) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
