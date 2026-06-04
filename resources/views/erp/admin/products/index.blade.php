@extends('erp::layouts.app')

@section('title', __('Ürünler'))
@section('page-title', __('Ürünler'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.products.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::input name="search" placeholder="{{ __('Ürün adı, SKU, barkod...') }}" :value="request('search')" />
            <x-admin-panel::select name="category_id"
                :options="$categories->pluck('name','id')->prepend(__('Tüm Kategoriler'), '')->toArray()"
                :selected="request('category_id')" />
            <x-admin-panel::select name="type"
                :options="['' => __('Tüm Tipler'), 'product' => __('Ürün'), 'service' => __('Hizmet'), 'consumable' => __('Sarf Malzeme')]"
                :selected="request('type')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>

        @can('erp.products.create')
            <x-admin-panel::button href="{{ route('erp.products.create') }}" icon="plus" variant="primary">
                {{ __('Yeni Ürün') }}
            </x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('SKU'), __('Ürün Adı'), __('Kategori'), __('Satış Fiyatı'), __('Toplam Stok'), __('Durum'), '']">
            @forelse($products as $product)
                <tr>
                    <td class="font-monospace small">{{ $product->sku }}</td>
                    <td>
                        <a href="{{ route('erp.products.show', $product) }}" class="fw-medium">{{ $product->name }}</a>
                        <div class="text-muted small">{{ $product->unit?->abbreviation }}</div>
                    </td>
                    <td>{{ $product->category?->name ?? '-' }}</td>
                    <td>{{ $erpFormat->money($product->sale_price) }}</td>
                    <td>
                        @if($product->track_stock)
                            @php $total = $product->totalStock(); @endphp
                            <x-admin-panel::badge variant="{{ $total <= $product->reorder_point && $product->reorder_point > 0 ? 'danger' : 'success' }}">
                                {{ number_format($total, 2) }}
                            </x-admin-panel::badge>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <x-admin-panel::badge variant="{{ $product->is_active ? 'success' : 'secondary' }}">
                            {{ $product->is_active ? __('Aktif') : __('Pasif') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.products.update')
                            <x-admin-panel::button href="{{ route('erp.products.edit', $product) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.products.delete')
                            <form method="POST" action="{{ route('erp.products.destroy', $product) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Bu ürünü silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Ürün bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    <div class="mt-3">{{ $products->links() }}</div>
@endsection
