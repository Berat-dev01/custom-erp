@extends('erp::layouts.app')

@section('title', __('Müşteriler'))
@section('page-title', __('Müşteriler'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.customers.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::input name="search" placeholder="{{ __('Ad, e-posta, vergi no...') }}" :value="request('search')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'active' => __('Aktif'), 'inactive' => __('Pasif')]"
                :selected="request('status')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.customers.create')
            <x-admin-panel::button href="{{ route('erp.customers.create') }}" icon="plus" variant="primary">{{ __('Yeni Müşteri') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Ad'), __('E-posta'), __('Telefon'), __('Ödeme Vadesi'), __('Kredi Limiti'), __('Durum'), '']">
            @forelse($customers as $c)
                <tr>
                    <td><a href="{{ route('erp.customers.show', $c) }}" class="fw-medium">{{ $c->name }}</a></td>
                    <td>{{ $c->email ?? '-' }}</td>
                    <td>{{ $c->phone ?? '-' }}</td>
                    <td>{{ $c->payment_terms_days }} {{ __('gün') }}</td>
                    <td>{{ $erpFormat->money($c->credit_limit) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $c->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $c->status === 'active' ? __('Aktif') : __('Pasif') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.customers.update')
                            <x-admin-panel::button href="{{ route('erp.customers.edit', $c) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.customers.delete')
                            <form method="POST" action="{{ route('erp.customers.destroy', $c) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Müşteri bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $customers->links() }}</div>
@endsection
