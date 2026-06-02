@extends('erp::layouts.app')

@section('title', __('Depolar'))
@section('page-title', __('Depolar'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('erp.warehouses.create')
            <x-admin-panel::button href="{{ route('erp.warehouses.create') }}" icon="plus" variant="primary">
                {{ __('Yeni Depo') }}
            </x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Ad'), __('Kod'), __('Adres'), __('Ürün Sayısı'), __('Varsayılan'), __('Durum'), '']">
            @forelse($warehouses as $wh)
                <tr>
                    <td>{{ $wh->name }}</td>
                    <td class="font-monospace">{{ $wh->code }}</td>
                    <td>{{ $wh->address ?? '-' }}</td>
                    <td>{{ $wh->stock_levels_count }}</td>
                    <td>
                        @if($wh->is_default)
                            <x-admin-panel::badge variant="primary">{{ __('Varsayılan') }}</x-admin-panel::badge>
                        @endif
                    </td>
                    <td>
                        <x-admin-panel::badge variant="{{ $wh->is_active ? 'success' : 'secondary' }}">
                            {{ $wh->is_active ? __('Aktif') : __('Pasif') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.warehouses.update')
                            <x-admin-panel::button href="{{ route('erp.warehouses.edit', $wh) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.warehouses.delete')
                            <form method="POST" action="{{ route('erp.warehouses.destroy', $wh) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Bu depoyu silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Depo bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $warehouses->links() }}</div>
@endsection
