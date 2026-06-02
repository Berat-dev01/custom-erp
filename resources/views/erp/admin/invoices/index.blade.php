@extends('erp::layouts.app')

@section('title', __('Faturalar'))
@section('page-title', __('Faturalar'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.invoices.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="type"
                :options="['' => __('Tüm Tipler'), 'sale' => __('Satış'), 'purchase' => __('Alış'), 'credit_note' => __('Kredi Notu')]"
                :selected="request('type')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'draft' => __('Taslak'), 'sent' => __('Gönderildi'), 'partial' => __('Kısmi Ödeme'), 'paid' => __('Ödendi'), 'overdue' => __('Vadesi Geçti'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::input name="date_to" type="date" :value="request('date_to')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.invoices.create')
            <x-admin-panel::button href="{{ route('erp.invoices.create') }}" icon="plus" variant="primary">{{ __('Yeni Fatura') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Fatura No'), __('Tip'), __('Tarih'), __('Vade'), __('Toplam'), __('Ödenen'), __('Kalan'), __('Durum'), '']">
            @forelse($invoices as $inv)
                <tr>
                    <td><a href="{{ route('erp.invoices.show', $inv) }}" class="fw-medium font-monospace">{{ $inv->invoice_number }}</a></td>
                    <td><x-admin-panel::badge variant="secondary">{{ __($inv->type) }}</x-admin-panel::badge></td>
                    <td>{{ $erpFormat->date($inv->issue_date) }}</td>
                    <td class="{{ $inv->isOverdue() ? 'text-danger fw-medium' : '' }}">{{ $erpFormat->date($inv->due_date) }}</td>
                    <td>{{ $erpFormat->money($inv->total, $inv->currency) }}</td>
                    <td>{{ $erpFormat->money($inv->paid_amount, $inv->currency) }}</td>
                    <td>{{ $erpFormat->money($inv->remainingAmount(), $inv->currency) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($inv->status) { 'paid' => 'success', 'overdue' => 'danger', 'draft' => 'secondary', 'partial' => 'warning', 'cancelled' => 'danger', default => 'info' } }}">
                            {{ __($inv->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.invoices.pdf', $inv) }}" size="sm" variant="ghost" icon="download" target="_blank" />
                        @can('erp.invoices.delete')
                            @if($inv->status === 'draft')
                                <form method="POST" action="{{ route('erp.invoices.destroy', $inv) }}" style="display:inline">
                                    @csrf @method('DELETE')
                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                        onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">{{ __('Fatura bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $invoices->links() }}</div>
@endsection
