@extends('erp::layouts.app')

@section('title', __('Tedarikçiler'))
@section('page-title', __('Tedarikçiler'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.suppliers.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::input name="search" placeholder="{{ __('Ad, e-posta, kod...') }}" :value="request('search')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'active' => __('Aktif'), 'inactive' => __('Pasif')]"
                :selected="request('status')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.suppliers.create')
            <x-admin-panel::button href="{{ route('erp.suppliers.create') }}" icon="plus" variant="primary">{{ __('Yeni Tedarikçi') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Ad'), __('Kod'), __('E-posta'), __('Telefon'), __('İletişim Kişisi'), __('Durum'), '']">
            @forelse($suppliers as $s)
                <tr>
                    <td><a href="{{ route('erp.suppliers.show', $s) }}" class="fw-medium">{{ $s->name }}</a></td>
                    <td>{{ $s->code ?? '-' }}</td>
                    <td>{{ $s->email ?? '-' }}</td>
                    <td>{{ $s->phone ?? '-' }}</td>
                    <td>{{ $s->contact_person ?? '-' }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $s->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $s->status === 'active' ? __('Aktif') : __('Pasif') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.suppliers.update')
                            <x-admin-panel::button href="{{ route('erp.suppliers.edit', $s) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.suppliers.delete')
                            <form method="POST" action="{{ route('erp.suppliers.destroy', $s) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Tedarikçi bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $suppliers->links() }}</div>
@endsection
