@extends('erp::layouts.app')
@section('title', __('Projeler'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Proje Adı')], ['label' => __('Müşteri')], ['label' => __('Durum')],
            ['label' => __('Bütçe')], ['label' => __('Görev Sayısı')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-projects">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Projeler') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.projects.export')<x-admin-panel::export-button :url="route('erp.projects.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-projects" />@endcan
                @can('erp.projects.create')<x-admin-panel::button :href="route('erp.projects.create')" icon="plus">{{ __('Yeni Proje') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-projects-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.projects.index')" :reset-url="route('erp.projects.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Proje adı..." />
                    <x-admin-panel::select name="status" label="Durum" :options="['planning'=>__('Planlama'),'active'=>__('Aktif'),'on_hold'=>__('Beklemede'),'completed'=>__('Tamamlandı'),'cancelled'=>__('İptal')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                    <x-admin-panel::select name="customer_id" label="Müşteri" :options="$customers" :selected="$filters['customer_id']" placeholder="Tüm müşteriler" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'name'=>__('Ad'),'start_date'=>__('Başlangıç'),'budget'=>__('Bütçe')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-projects-bulk" method="POST" action="{{ route('erp.projects.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-projects-bulk" checkbox-selector=".erp-project-selector" label="proje">
                    @can('erp.projects.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili projeleri silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Projeler') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($projects as $project)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $project->id }}" class="form-check-input erp-project-selector"></td>
                            <td><a href="{{ route('erp.projects.show', $project) }}" class="fw-medium">{{ $project->name }}</a></td>
                            <td>{{ $project->customer?->name ?? '-' }}</td>
                            <td><x-admin-panel::badge variant="{{ match($project->status) { 'active'=>'success','planning'=>'info','on_hold'=>'warning','completed'=>'secondary',default=>'secondary' } }}">{{ $project->status }}</x-admin-panel::badge></td>
                            <td>{{ $project->budget ? $erpFormat->money($project->budget) : '-' }}</td>
                            <td>{{ $project->tasks_count }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.projects.show', $project)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.projects.update')<x-admin-panel::button :href="route('erp.projects.edit', $project)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.projects.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-project-delete-{{ $project->id }}" data-admin-confirm="{{ __('Bu projeyi silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Proje bulunamadı.'),'actionUrl'=>route('erp.projects.create'),'actionLabel'=>__('Yeni Proje'),'actionPermission'=>'erp.projects.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $projects->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$projects" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($projects as $project)
            @can('erp.projects.delete')
                <form id="erp-project-delete-{{ $project->id }}" method="POST" action="{{ route('erp.projects.destroy', $project) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
