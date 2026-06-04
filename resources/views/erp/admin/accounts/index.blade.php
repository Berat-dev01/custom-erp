@extends('erp::layouts.app')

@section('title', __('Hesap Planı'))
@section('page-title', __('Hesap Planı'))

@section('content')
    <div class="mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="type"
                :options="['' => __('Tüm Hesaplar'), 'asset' => __('Varlık'), 'liability' => __('Yükümlülük'), 'equity' => __('Özkaynak'), 'revenue' => __('Gelir'), 'expense' => __('Gider')]"
                :selected="request('type')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Kod'), __('Hesap Adı'), __('Tip'), __('Normal Bakiye'), __('Bakiye'), '']">
            @forelse($accounts as $account)
                <tr>
                    <td class="font-monospace fw-medium">{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($account->type) { 'asset' => 'info', 'liability' => 'warning', 'equity' => 'primary', 'revenue' => 'success', 'expense' => 'danger', default => 'secondary' } }}">
                            {{ __($account->type) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-muted small">{{ $account->normal_balance === 'debit' ? __('Borç') : __('Alacak') }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($account->balance()) }}</td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.accounts.show', $account) }}" size="sm" variant="ghost" icon="list">
                            {{ __('Hesap Defteri') }}
                        </x-admin-panel::button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Hesap bulunamadı. Hesap planı seeder\'ını çalıştırın.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
