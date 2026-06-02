@extends('erp::layouts.app')

@section('title', $journalEntry->entry_number)
@section('page-title', $journalEntry->entry_number)

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.journal-entries.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Fişlere Dön') }}
        </x-admin-panel::button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <x-admin-panel::card>
                <div class="row g-3">
                    <div class="col-sm-4"><div class="text-muted small">{{ __('Fiş No') }}</div><div class="fw-semibold font-monospace">{{ $journalEntry->entry_number }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">{{ __('Tarih') }}</div><div>{{ $erpFormat->date($journalEntry->entry_date) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">{{ __('Tip') }}</div><div><x-admin-panel::badge variant="secondary">{{ __($journalEntry->type) }}</x-admin-panel::badge></div></div>
                    <div class="col-sm-8"><div class="text-muted small">{{ __('Açıklama') }}</div><div>{{ $journalEntry->description }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">{{ __('Referans') }}</div><div>{{ $journalEntry->reference ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">{{ __('Durum') }}</div>
                        <div><x-admin-panel::badge variant="{{ $journalEntry->status === 'posted' ? 'success' : 'secondary' }}">{{ __($journalEntry->status) }}</x-admin-panel::badge></div>
                    </div>
                    <div class="col-sm-8"><div class="text-muted small">{{ __('Oluşturan') }}</div><div>{{ $journalEntry->createdBy?->name }}</div></div>
                </div>
            </x-admin-panel::card>
        </div>
        <div class="col-md-4">
            <x-admin-panel::card>
                <div class="text-muted small mb-1">{{ __('Borç Toplam') }}</div>
                <div class="fw-bold fs-5">{{ $erpFormat->money($journalEntry->totalDebit()) }}</div>
                <div class="text-muted small mb-1 mt-2">{{ __('Alacak Toplam') }}</div>
                <div class="fw-bold fs-5">{{ $erpFormat->money($journalEntry->totalCredit()) }}</div>
                <div class="mt-2">
                    @if($journalEntry->isBalanced())
                        <x-admin-panel::badge variant="success">{{ __('Dengeli') }}</x-admin-panel::badge>
                    @else
                        <x-admin-panel::badge variant="danger">{{ __('Dengesiz') }}</x-admin-panel::badge>
                    @endif
                </div>
            </x-admin-panel::card>
        </div>
    </div>

    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3">{{ __('Fiş Kalemleri') }}</h6>
        <x-admin-panel::table :headers="[__('Hesap Kodu'), __('Hesap Adı'), __('Açıklama'), __('Borç'), __('Alacak')]">
            @foreach($journalEntry->lines as $line)
                <tr>
                    <td class="font-monospace fw-medium">{{ $line->account?->code }}</td>
                    <td>{{ $line->account?->name }}</td>
                    <td class="text-muted small">{{ $line->description }}</td>
                    <td>{{ $line->debit > 0 ? $erpFormat->money($line->debit) : '-' }}</td>
                    <td>{{ $line->credit > 0 ? $erpFormat->money($line->credit) : '-' }}</td>
                </tr>
            @endforeach
            <tr class="fw-bold border-top">
                <td colspan="3">{{ __('Toplam') }}</td>
                <td>{{ $erpFormat->money($journalEntry->totalDebit()) }}</td>
                <td>{{ $erpFormat->money($journalEntry->totalCredit()) }}</td>
            </tr>
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
