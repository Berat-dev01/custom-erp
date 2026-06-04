@extends('erp::layouts.app')

@section('title', __('Yevmiye Fişleri'))
@section('page-title', __('Yevmiye Fişleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.journal-entries.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="type" :options="['' => __('Tüm Tipler'), 'manual' => __('Manuel'), 'invoice' => __('Fatura'), 'payment' => __('Ödeme'), 'payroll' => __('Bordro'), 'depreciation' => __('Amortisman'), 'adjustment' => __('Düzeltme')]" :selected="request('type')" />
            <x-admin-panel::select name="status" :options="['' => __('Tüm Durumlar'), 'draft' => __('Taslak'), 'posted' => __('İşlendi')]" :selected="request('status')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::input name="date_to" type="date" :value="request('date_to')" />
            <x-admin-panel::input name="search" placeholder="{{ __('Fiş no, açıklama...') }}" :value="request('search')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.journal_entries.create')
            <x-admin-panel::button href="{{ route('erp.journal-entries.create') }}" icon="plus" variant="primary">{{ __('Manuel Fiş') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Fiş No'), __('Tarih'), __('Tip'), __('Açıklama'), __('Referans'), __('Durum'), '']">
            @forelse($entries as $entry)
                <tr>
                    <td><a href="{{ route('erp.journal-entries.show', $entry) }}" class="fw-medium font-monospace">{{ $entry->entry_number }}</a></td>
                    <td>{{ $erpFormat->date($entry->entry_date) }}</td>
                    <td><x-admin-panel::badge variant="secondary">{{ __($entry->type) }}</x-admin-panel::badge></td>
                    <td>{{ Str::limit($entry->description, 60) }}</td>
                    <td class="text-muted small">{{ $entry->reference }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $entry->status === 'posted' ? 'success' : 'secondary' }}">
                            {{ __($entry->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.journal-entries.show', $entry) }}" size="sm" variant="ghost" icon="eye" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Yevmiye fişi bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $entries->links() }}</div>
@endsection
