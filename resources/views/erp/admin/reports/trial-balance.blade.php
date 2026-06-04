@extends('erp::layouts.app')

@section('title', __('Mizan'))
@section('page-title', __('Mizan'))

@section('content')
    <form method="GET" class="d-flex gap-2 mb-4 align-items-end flex-wrap">
        <x-admin-panel::input name="date_from" type="date" :label="__('Başlangıç')" :value="$from->format('Y-m-d')" />
        <x-admin-panel::input name="date_to"   type="date" :label="__('Bitiş')"     :value="$to->format('Y-m-d')" />
        <div class="pb-1"><x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Uygula') }}</x-admin-panel::button></div>
    </form>

    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3">{{ __('Mizan') }} — {{ $erpFormat->date($from) }} / {{ $erpFormat->date($to) }}</h6>
        <x-admin-panel::table :headers="[__('Kod'), __('Hesap Adı'), __('Toplam Borç'), __('Toplam Alacak'), __('Bakiye')]">
            @php $sumDebit = 0; $sumCredit = 0; $sumBalance = 0; @endphp
            @forelse($rows as $row)
                @php $sumDebit += $row['total_debit']; $sumCredit += $row['total_credit']; $sumBalance += $row['balance']; @endphp
                <tr>
                    <td class="font-monospace fw-medium">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $erpFormat->money($row['total_debit']) }}</td>
                    <td>{{ $erpFormat->money($row['total_credit']) }}</td>
                    <td class="{{ $row['balance'] < 0 ? 'text-danger' : '' }} fw-medium">{{ $erpFormat->money($row['balance']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Bu dönem için yevmiye fişi bulunamadı.') }}</td></tr>
            @endforelse
            @if($rows->isNotEmpty())
                <tr class="fw-bold border-top">
                    <td colspan="2">{{ __('TOPLAM') }}</td>
                    <td>{{ $erpFormat->money($sumDebit) }}</td>
                    <td>{{ $erpFormat->money($sumCredit) }}</td>
                    <td class="{{ abs($sumDebit - $sumCredit) < 0.01 ? 'text-success' : 'text-danger' }}">
                        {{ $erpFormat->money($sumBalance) }}
                        @if(abs($sumDebit - $sumCredit) < 0.01)
                            <x-admin-panel::badge variant="success" class="ms-1">{{ __('Dengeli') }}</x-admin-panel::badge>
                        @else
                            <x-admin-panel::badge variant="danger" class="ms-1">{{ __('Dengesiz!') }}</x-admin-panel::badge>
                        @endif
                    </td>
                </tr>
            @endif
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
