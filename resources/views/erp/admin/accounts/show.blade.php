@extends('erp::layouts.app')

@section('title', $account->code.' — '.$account->name)
@section('page-title', $account->code.' — '.$account->name)

@section('content')
    <div class="mb-3 d-flex gap-2">
        <x-admin-panel::button href="{{ route('erp.accounts.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Hesap Planına Dön') }}
        </x-admin-panel::button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('Güncel Bakiye')" :value="$erpFormat->money($balance)" icon="trending-up" />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('Tip')" :value="__($account->type)" icon="tag" />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('Normal Bakiye')" :value="$account->normal_balance === 'debit' ? __('Borç') : __('Alacak')" icon="arrow-right-left" />
        </div>
    </div>

    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
        <x-admin-panel::input name="date_from" type="date" :label="__('Başlangıç')" :value="request('date_from')" />
        <x-admin-panel::input name="date_to" type="date" :label="__('Bitiş')" :value="request('date_to')" />
        <div class="align-self-end pb-1">
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </div>
    </form>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Fiş No'), __('Tarih'), __('Açıklama'), __('Borç'), __('Alacak')]">
            @forelse($lines as $line)
                <tr>
                    <td>
                        <a href="{{ route('erp.journal-entries.show', $line->journal_entry_id) }}" class="font-monospace small fw-medium">
                            {{ $line->journalEntry?->entry_number }}
                        </a>
                    </td>
                    <td>{{ $erpFormat->date($line->journalEntry?->entry_date) }}</td>
                    <td>{{ $line->description ?: $line->journalEntry?->description }}</td>
                    <td>{{ $line->debit > 0 ? $erpFormat->money($line->debit) : '-' }}</td>
                    <td>{{ $line->credit > 0 ? $erpFormat->money($line->credit) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Hareket bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $lines->links() }}</div>
@endsection
