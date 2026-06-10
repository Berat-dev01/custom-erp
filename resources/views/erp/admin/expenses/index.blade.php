@extends('erp::layouts.app')
@section('title', __('Giderler'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Başlık')], ['label' => __('Kategori')], ['label' => __('Tutar')],
            ['label' => __('Tarih')], ['label' => __('Oluşturan')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-expenses">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Giderler') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.expenses.export')<x-admin-panel::export-button :url="route('erp.expenses.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-expenses" />@endcan
                @can('erp.expenses.create')<x-admin-panel::button :href="route('erp.expenses.create')" icon="plus">{{ __('Yeni Gider') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-expenses-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.expenses.index')" :reset-url="route('erp.expenses.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Başlık..." />
                    <x-admin-panel::select name="category" label="Kategori" :options="['office'=>__('Ofis'),'travel'=>__('Seyahat'),'utilities'=>__('Faturalar'),'salary'=>__('Maaş'),'rent'=>__('Kira'),'marketing'=>__('Pazarlama'),'other'=>__('Diğer')]" :selected="$filters['category']" placeholder="Tüm kategoriler" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::input name="date_from" type="date" label="Başlangıç" :value="$filters['date_from']" />
                    <x-admin-panel::input name="date_to" type="date" label="Bitiş" :value="$filters['date_to']" />
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['expense_date'=>__('Tarih'),'amount'=>__('Tutar'),'title'=>__('Başlık')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-expenses-bulk" method="POST" action="{{ route('erp.expenses.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-expenses-bulk" checkbox-selector=".erp-expense-selector" label="gider">
                    @can('erp.expenses.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili giderleri silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Giderler') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($expenses as $expense)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $expense->id }}" class="form-check-input erp-expense-selector"></td>
                            <td><strong>{{ $expense->title }}</strong></td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $erpFormat->money($expense->amount) }}</td>
                            <td>{{ $expense->expense_date?->format('d.m.Y') }}</td>
                            <td>{{ $expense->createdBy?->name ?? '-' }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    @can('erp.expenses.update')<x-admin-panel::button :href="route('erp.expenses.edit', $expense)" size="sm" variant="ghost" icon="pencil" />@endcan
                                    @can('erp.expenses.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-expense-delete-{{ $expense->id }}" data-admin-confirm="{{ __('Bu gideri silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Gider bulunamadı.'),'actionUrl'=>route('erp.expenses.create'),'actionLabel'=>__('Yeni Gider'),'actionPermission'=>'erp.expenses.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $expenses->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$expenses" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($expenses as $expense)
            @can('erp.expenses.delete')
                <form id="erp-expense-delete-{{ $expense->id }}" method="POST" action="{{ route('erp.expenses.destroy', $expense) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
