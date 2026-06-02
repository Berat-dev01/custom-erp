@extends('erp::layouts.app')

@section('title', __('Çek/Senet Portföyü'))
@section('page-title', __('Çek/Senet Portföyü'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    @if($dueSoon > 0)
        <x-admin-panel::alert type="warning" dismissible class="mb-3">
            {{ __(':count çek/senedin vadesi 7 gün içinde dolacak!', ['count' => $dueSoon]) }}
        </x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="type"
                :options="['' => __('Tümü'), 'received' => __('Alınan'), 'issued' => __('Verilen')]"
                :selected="request('type')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'portfolio' => __('Portföyde'), 'sent_to_bank' => __('Bankaya Verildi'), 'cashed' => __('Tahsil/Ödendi'), 'bounced' => __('Karşılıksız'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::input name="date_to"   type="date" :value="request('date_to')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.bank.manage')
            <x-admin-panel::button href="{{ route('erp.checks.create') }}" icon="plus" variant="primary">{{ __('Yeni Çek/Senet') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Tip'), __('Çek No'), __('Banka'), __('Tutar'), __('Vade'), __('Kalan Gün'), __('Durum'), '']">
            @forelse($checks as $check)
                @php $days = $check->daysUntilDue(); @endphp
                <tr>
                    <td>
                        <x-admin-panel::badge variant="{{ $check->type === 'received' ? 'success' : 'warning' }}">
                            {{ $check->type === 'received' ? __('Alınan') : __('Verilen') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="font-monospace fw-medium">{{ $check->check_number }}</td>
                    <td>{{ $check->bank_name }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($check->amount) }}</td>
                    <td class="{{ $check->isOverdue() ? 'text-danger fw-medium' : '' }}">{{ $erpFormat->date($check->due_date) }}</td>
                    <td>
                        @if(in_array($check->status, ['cashed', 'cancelled']))
                            <span class="text-muted">—</span>
                        @elseif($days < 0)
                            <x-admin-panel::badge variant="danger">{{ abs($days) }}g geç</x-admin-panel::badge>
                        @elseif($days <= 7)
                            <x-admin-panel::badge variant="warning">{{ $days }}g</x-admin-panel::badge>
                        @else
                            <span class="text-muted">{{ $days }}g</span>
                        @endif
                    </td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($check->status) { 'portfolio' => 'info', 'sent_to_bank' => 'secondary', 'cashed' => 'success', 'bounced' => 'danger', 'cancelled' => 'danger', default => 'secondary' } }}">
                            {{ __($check->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.bank.manage')
                            <form method="POST" action="{{ route('erp.checks.update-status', $check) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block" style="width:auto">
                                    @foreach(['portfolio','sent_to_bank','cashed','bounced','cancelled'] as $s)
                                        <option value="{{ $s }}" {{ $check->status === $s ? 'selected' : '' }}>{{ __($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @if($check->status !== 'cashed')
                                <form method="POST" action="{{ route('erp.checks.destroy', $check) }}" class="d-inline ms-1">
                                    @csrf @method('DELETE')
                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                        onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Çek/senet bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $checks->links() }}</div>
@endsection
