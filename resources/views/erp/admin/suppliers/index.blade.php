@extends('erp::layouts.app')

@section('title', __('Tedarikçiler'))
@section('page-title', __('Tedarikçiler'))

@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Ad')], ['label' => __('E-posta')], ['label' => __('Telefon')],
            ['label' => __('İletişim Kişisi')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="erp-suppliers">
        @include('erp::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">ERP</p>
                <h1>{{ __('Tedarikçiler') }}</h1>
            </div>
            <div class="crm-admin-actions">
                @can('erp.suppliers.export')
                    <x-admin-panel::export-button :url="route('erp.suppliers.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-suppliers" />
                @endcan
                @can('erp.suppliers.create')
                    <x-admin-panel::button :href="route('erp.suppliers.create')" icon="plus">{{ __('Yeni Tedarikçi') }}</x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="erp-suppliers-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.suppliers.index')" :reset-url="route('erp.suppliers.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Ad, e-posta, vergi no..." />
                    <x-admin-panel::select name="status" label="Durum" :options="['active'=>__('Aktif'),'inactive'=>__('Pasif')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'name'=>__('Ad'),'payment_terms_days'=>__('Ödeme vadesi')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>

            <form id="erp-suppliers-bulk" method="POST" action="{{ route('erp.suppliers.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-suppliers-bulk" checkbox-selector=".erp-supplier-selector" label="tedarikçi">
                    @can('erp.suppliers.delete')
                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                            data-admin-confirm="{{ __('Seçili tedarikçileri silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>
                    @endcan
                </x-admin-panel::bulk-actions>

                <x-admin-panel::card>
                    <x-slot:header>{{ __('Tedarikçiler') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($suppliers as $s)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $s->id }}" class="form-check-input erp-supplier-selector"></td>
                            <td><a href="{{ route('erp.suppliers.show', $s) }}" class="fw-medium">{{ $s->name }}</a></td>
                            <td>{{ $s->email ?? '-' }}</td>
                            <td>{{ $s->phone ?? '-' }}</td>
                            <td>{{ $s->contact_person ?? '-' }}</td>
                            <td><x-admin-panel::badge variant="{{ $s->status === 'active' ? 'success' : 'secondary' }}">{{ $s->status === 'active' ? __('Aktif') : __('Pasif') }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.suppliers.show', $s)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.suppliers.update')
                                        <x-admin-panel::button :href="route('erp.suppliers.edit', $s)" size="sm" variant="ghost" icon="pencil" />
                                    @endcan
                                    @can('erp.suppliers.delete')
                                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-supplier-delete-{{ $s->id }}" data-admin-confirm="{{ __('Bu tedarikçiyi silmek istediğinize emin misiniz?') }}" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state', ['title'=>__('Tedarikçi bulunamadı.'),'actionUrl'=>route('erp.suppliers.create'),'actionLabel'=>__('Yeni Tedarikçi'),'actionPermission'=>'erp.suppliers.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $suppliers->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$suppliers" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>

        @foreach($suppliers as $s)
            @can('erp.suppliers.delete')
                <form id="erp-supplier-delete-{{ $s->id }}" method="POST" action="{{ route('erp.suppliers.destroy', $s) }}" class="crm-hidden-form">
                    @csrf @method('DELETE')
                </form>
            @endcan
        @endforeach
    </section>
@endsection
