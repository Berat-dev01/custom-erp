@extends('erp::layouts.app')

@section('title', __('Sabit Kıymetler'))
@section('page-title', __('Sabit Kıymetler'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.assets.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::input name="search" placeholder="{{ __('Ad, kod, seri no...') }}" :value="request('search')" />
            <x-admin-panel::select name="category_id"
                :options="$categories->pluck('name','id')->prepend(__('Tüm Kategoriler'), '')->toArray()"
                :selected="request('category_id')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'active' => __('Aktif'), 'in_repair' => __('Onarımda'), 'disposed' => __('Elden Çıkarıldı')]"
                :selected="request('status')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.assets.create')
            <x-admin-panel::button href="{{ route('erp.assets.create') }}" icon="plus" variant="primary">{{ __('Yeni Varlık') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Varlık'), __('Kategori'), __('Satın Alma Fiyatı'), __('Güncel Değer'), __('Atanan'), __('Satın Alma Tarihi'), __('Durum'), '']">
            @forelse($assets as $asset)
                <tr>
                    <td>
                        <a href="{{ route('erp.assets.show', $asset) }}" class="fw-medium">{{ $asset->name }}</a>
                        <div class="text-muted small font-monospace">{{ $asset->asset_code }}</div>
                    </td>
                    <td>{{ $asset->category?->name }}</td>
                    <td>{{ $erpFormat->money($asset->purchase_price) }}</td>
                    <td>{{ $erpFormat->money($asset->current_value) }}</td>
                    <td>{{ $asset->assignedTo?->full_name ?? '-' }}</td>
                    <td>{{ $erpFormat->date($asset->purchase_date) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($asset->status) { 'active' => 'success', 'in_repair' => 'warning', 'disposed' => 'danger', default => 'secondary' } }}">
                            {{ __($asset->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.assets.update')
                            <x-admin-panel::button href="{{ route('erp.assets.edit', $asset) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.assets.delete')
                            <form method="POST" action="{{ route('erp.assets.destroy', $asset) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Varlık bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $assets->links() }}</div>
@endsection
