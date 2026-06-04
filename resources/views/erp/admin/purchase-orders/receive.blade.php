@extends('erp::layouts.app')

@section('title', __('Mal Teslim Al') . ' — ' . $purchaseOrder->po_number)
@section('page-title', __('Mal Teslim Al'))

@section('content')
    <x-admin-panel::card>
        <div class="mb-4">
            <p class="text-muted mb-1">{{ __('Sipariş') }}: <strong>{{ $purchaseOrder->po_number }}</strong></p>
            <p class="text-muted mb-0">{{ __('Tedarikçi') }}: <strong>{{ $purchaseOrder->supplier?->name }}</strong> &nbsp;|&nbsp;
            {{ __('Depo') }}: <strong>{{ $purchaseOrder->warehouse?->name }}</strong></p>
        </div>

        <form method="POST" action="{{ route('erp.purchase-orders.store-receiving', $purchaseOrder) }}">
            @csrf

            <x-admin-panel::table :headers="[__('Ürün'), __('Sipariş Miktarı'), __('Daha Önce Teslim'), __('Bekleyen'), __('Bu Teslimat')]">
                @foreach($purchaseOrder->items as $item)
                    @php $pending = $item->pendingQuantity(); @endphp
                    <tr>
                        <td>
                            {{ $item->product?->name }}
                            <div class="text-muted small font-monospace">{{ $item->product?->sku }}</div>
                        </td>
                        <td>{{ number_format($item->quantity, 3) }}</td>
                        <td>{{ number_format($item->received_quantity, 3) }}</td>
                        <td>{{ number_format($pending, 3) }}</td>
                        <td>
                            @if($pending > 0)
                                <input type="number" step="0.001" min="0" max="{{ $pending }}"
                                    name="items[{{ $item->id }}]"
                                    value="{{ $pending }}"
                                    class="form-control form-control-sm" style="width:130px">
                            @else
                                <span class="text-success">{{ __('Tamamlandı') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin-panel::table>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="package">{{ __('Teslim Al ve Stok Güncelle') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.purchase-orders.show', $purchaseOrder) }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
