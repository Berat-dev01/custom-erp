@extends('erp::layouts.app')
@section('title', __('Departmanlar'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Ad')], ['label' => __('Müdür')], ['label' => __('Çalışan Sayısı')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-departments">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Departmanlar') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.departments.create')<x-admin-panel::button :href="route('erp.departments.create')" icon="plus">{{ __('Yeni Departman') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-departments-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.departments.index')" :reset-url="route('erp.departments.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Departman adı..." />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['name'=>__('Ad'),'created_at'=>__('Eklenme')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-departments-bulk" method="POST" action="{{ route('erp.departments.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-departments-bulk" checkbox-selector=".erp-dept-selector" label="departman">
                    @can('erp.departments.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili departmanları silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Departmanlar') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($departments as $dept)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $dept->id }}" class="form-check-input erp-dept-selector"></td>
                            <td><a href="{{ route('erp.departments.show', $dept) }}" class="fw-medium">{{ $dept->name }}</a></td>
                            <td>{{ $dept->manager ? $dept->manager->last_name.' '.$dept->manager->first_name : '-' }}</td>
                            <td>{{ $dept->employees_count }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.departments.show', $dept)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.departments.update')<x-admin-panel::button :href="route('erp.departments.edit', $dept)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.departments.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-dept-delete-{{ $dept->id }}" data-admin-confirm="{{ __('Bu departmanı silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('erp::admin.partials.empty-state',['title'=>__('Departman bulunamadı.'),'actionUrl'=>route('erp.departments.create'),'actionLabel'=>__('Yeni Departman'),'actionPermission'=>'erp.departments.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <x-admin-panel::pagination :paginator="$departments" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($departments as $dept)
            @can('erp.departments.delete')
                <form id="erp-dept-delete-{{ $dept->id }}" method="POST" action="{{ route('erp.departments.destroy', $dept) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
