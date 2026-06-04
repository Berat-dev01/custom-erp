@extends('erp::layouts.app')

@section('title', __('Manuel Stok Hareketi'))
@section('page-title', __('Manuel Stok Hareketi'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.stock-movements.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::select name="product_id" :label="__('Ürün')" required
                        :options="$products->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('product_id', request('product_id'))" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="warehouse_id" :label="__('Depo')" required
                        :options="$warehouses->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('warehouse_id')" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="type" :label="__('Hareket Tipi')" required
                        :options="['in' => __('Giriş (Stok Artış)'), 'out' => __('Çıkış (Stok Azalış)'), 'adjustment' => __('Düzeltme')]"
                        :selected="old('type', 'in')" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="quantity" type="number" step="0.001" :label="__('Miktar')" :value="old('quantity')" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="unit_cost" type="number" step="0.01" :label="__('Birim Maliyet')" :value="old('unit_cost')" />
                </div>
                <div class="col-12">
                    <x-admin-panel::input name="notes" :label="__('Notlar')" :value="old('notes')" />
                </div>
            </div>

            @if($errors->any())
                <div class="mt-3">
                    <x-admin-panel::alert type="error">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </x-admin-panel::alert>
                </div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Hareketi Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.stock-movements.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
