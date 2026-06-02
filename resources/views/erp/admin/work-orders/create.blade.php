@extends('erp::layouts.app')

@section('title', __('Yeni İş Emri'))
@section('page-title', __('Yeni İş Emri'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.work-orders.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('İş Emirlerine Dön') }}</x-admin-panel::button>
    </div>

    <form method="POST" action="{{ route('erp.work-orders.store') }}">
        @csrf
        <x-admin-panel::card>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-admin-panel::select name="bom_id" :label="__('BOM (Ürün Ağacı)')"
                        :options="$boms->map(fn($b) => $b->product?->name.' v'.$b->version)->toArray()"
                        :selected="old('bom_id', request('bom_id'))" required />
                    @error('bom_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="warehouse_id" :label="__('Depo')"
                        :options="$warehouses->pluck('name','id')->toArray()"
                        :selected="old('warehouse_id')" required />
                </div>
                <div class="col-md-2">
                    <x-admin-panel::input name="planned_quantity" :label="__('Planlanan Miktar')" type="number" step="0.001" min="0.001" :value="old('planned_quantity','1')" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="planned_start" :label="__('Başlangıç Tarihi')" type="date" :value="old('planned_start', today()->format('Y-m-d'))" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="planned_end" :label="__('Bitiş Tarihi')" type="date" :value="old('planned_end', today()->addDays(7)->format('Y-m-d'))" required />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="notes" :label="__('Notlar')" :value="old('notes')" rows="2" />
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <x-admin-panel::button href="{{ route('erp.work-orders.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Oluştur') }}</x-admin-panel::button>
            </div>
        </x-admin-panel::card>
    </form>
@endsection
