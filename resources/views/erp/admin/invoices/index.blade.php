@extends('erp::layouts.app')
@section('title', __('Faturalar'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input">'), 'width' => '36px'],
            ['label' => __('Fatura No')], ['label' => __('Müşteri/Tedarikçi')], ['label' => __('Tarih')],
            ['label' => __('Toplam')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-invoices">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div><p class="crm-admin-eyebrow">ERP</p><h1>{{ __('Faturalar') }}</h1></div>
            <div class="crm-admin-actions">
                @can('erp.invoices.export')<x-admin-panel::export-button :url="route('erp.invoices.export')" :columns="$exportColumns" :formats="$exportFormats" module="erp-invoices" />@endcan
                @can('erp.invoices.create')<x-admin-panel::button :href="route('erp.invoices.create')" icon="plus">{{ __('Yeni Fatura') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-invoices-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.invoices.index')" :reset-url="route('erp.invoices.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Fatura no..." />
                    <x-admin-panel::select name="status" label="Durum" :options="['draft'=>__('Taslak'),'sent'=>__('Gönderildi'),'partial'=>__('Kısmi ödendi'),'paid'=>__('Ödendi'),'overdue'=>__('Gecikmiş'),'cancelled'=>__('İptal')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'invoice_number'=>__('Fatura no'),'issue_date'=>__('Tarih'),'total'=>__('Toplam')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <form id="erp-invoices-bulk" method="POST" action="{{ route('erp.invoices.bulk-delete') }}">
                @csrf @method('DELETE')
                <x-admin-panel::bulk-actions form="erp-invoices-bulk" checkbox-selector=".erp-inv-selector" label="fatura">
                    @can('erp.invoices.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" data-admin-confirm="{{ __('Seçili faturaları silmek istediğinize emin misiniz?') }}">{{ __('Seçilenleri Sil') }}</x-admin-panel::button>@endcan
                </x-admin-panel::bulk-actions>
                <x-admin-panel::card>
                    <x-slot:header>{{ __('Faturalar') }}</x-slot:header>
                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><input type="checkbox" name="record_ids[]" value="{{ $invoice->id }}" class="form-check-input erp-inv-selector"></td>
                            <td><a href="{{ route('erp.invoices.show', $invoice) }}" class="fw-medium font-monospace">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->invoiceable?->name ?? '-' }}</td>
                            <td>{{ $invoice->issue_date?->format('d.m.Y') }}</td>
                            <td>{{ $erpFormat->money($invoice->total) }}</td>
                            <td><x-admin-panel::badge variant="{{ match($invoice->status) { 'paid'=>'success','sent'=>'info','partial'=>'warning','overdue'=>'danger',default=>'secondary' } }}">{{ $invoice->status }}</x-admin-panel::badge></td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.invoices.show', $invoice)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.invoices.delete')<x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="erp-inv-delete-{{ $invoice->id }}" data-admin-confirm="{{ __('Bu faturayı silmek istediğinize emin misiniz?') }}" />@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('Fatura bulunamadı.'),'actionUrl'=>route('erp.invoices.create'),'actionLabel'=>__('Yeni Fatura'),'actionPermission'=>'erp.invoices.create'])</td></tr>
                    @endforelse
                    </x-admin-panel::table>
                    <span hidden data-export-total="{{ $invoices->total() }}"></span>
                    <x-admin-panel::pagination :paginator="$invoices" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>
        @foreach($invoices as $invoice)
            @can('erp.invoices.delete')
                <form id="erp-inv-delete-{{ $invoice->id }}" method="POST" action="{{ route('erp.invoices.destroy', $invoice) }}" class="crm-hidden-form">@csrf @method('DELETE')</form>
            @endcan
        @endforeach
    </section>
@endsection
