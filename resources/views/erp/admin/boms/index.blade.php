@extends('erp::layouts.app')

@section('title', __('Ürün Ağaçları (BOM)'))
@section('page-title', __('Ürün Ağaçları (BOM)'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('erp.manufacturing.manage')
            <x-admin-panel::button href="{{ route('erp.boms.create') }}" icon="plus" variant="primary">{{ __('Yeni BOM') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Ürün'), __('Versiyon'), __('Üretim Miktarı'), __('Bileşen Sayısı'), __('Durum'), '']">
            @forelse($boms as $bom)
                <tr>
                    <td><a href="{{ route('erp.boms.show', $bom) }}" class="fw-medium">{{ $bom->product?->name }}</a>
                        <div class="text-muted small font-monospace">{{ $bom->product?->sku }}</div>
                    </td>
                    <td>v{{ $bom->version }}</td>
                    <td>{{ number_format($bom->quantity, 3, ',', '.') }}</td>
                    <td>{{ $bom->components_count ?? '-' }}</td>
                    <td><x-admin-panel::badge variant="{{ $bom->is_active ? 'success' : 'secondary' }}">{{ $bom->is_active ? __('Aktif') : __('Pasif') }}</x-admin-panel::badge></td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.boms.show', $bom) }}" size="sm" variant="ghost" icon="eye" />
                        @can('erp.manufacturing.manage')
                            <form method="POST" action="{{ route('erp.boms.destroy', $bom) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('BOM bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $boms->links() }}</div>
@endsection
