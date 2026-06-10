@extends('erp::layouts.app')
@section('title', __('Çalışanlar'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Sicil No')], ['label' => __('Ad Soyad')], ['label' => __('Departman')],
            ['label' => __('Pozisyon')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-employees">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Çalışanlar') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.employees.export')<x-admin-panel::export-button :url="route('erp.employees.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-employees" />@endcan
                @can('erp.employees.create')<x-admin-panel::button :href="route('erp.employees.create')" icon="plus">{{ __('Yeni Çalışan') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-employees-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.employees.index')" :reset-url="route('erp.employees.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Ad, soyad, e-posta, sicil no..." />
                    <x-admin-panel::select name="department_id" label="Departman" :options="$departments" :selected="$filters['department_id']" placeholder="Tüm departmanlar" />
                    <x-admin-panel::select name="status" label="Durum" :options="['active'=>__('Aktif'),'terminated'=>__('Ayrıldı'),'on_leave'=>__('İzinde')]" :selected="$filters['status']" placeholder="Tümü" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['last_name'=>__('Soyad'),'first_name'=>__('Ad'),'hire_date'=>__('İşe giriş'),'employee_number'=>__('Sicil no')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-employees-bulk" method="POST" action="{{ route('erp.employees.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-employees-bulk" checkbox-selector=".erp-employee-selector" label="çalışan">
                    @can('erp.employees.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili çalışanları silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Çalışanlar') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($employees as $employee)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $employee->id }}" class="form-check-input erp-employee-selector"></td>
                            <td><span class="font-monospace small">{{ $employee->employee_number }}</span></td>
                            <td><a href="{{ route('erp.employees.show', $employee) }}" class="fw-medium">{{ $employee->last_name }} {{ $employee->first_name }}</a></td>
                            <td>{{ $employee->department?->name ?? '-' }}</td>
                            <td>{{ $employee->position?->title ?? '-' }}</td>
                            <td><x-admin-panel::badge variant="{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ $employee->status }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.employees.show', $employee)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.employees.update')<x-admin-panel::button :href="route('erp.employees.edit', $employee)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.employees.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-employee-delete-{{ $employee->id }}" data-admin-confirm="{{ __('Bu çalışanı silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Çalışan bulunamadı.'),'actionUrl'=>route('erp.employees.create'),'actionLabel'=>__('Yeni Çalışan'),'actionPermission'=>'erp.employees.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $employees->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$employees" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($employees as $employee)
            @can('erp.employees.delete')
                <form id="erp-employee-delete-{{ $employee->id }}" method="POST" action="{{ route('erp.employees.destroy', $employee) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
