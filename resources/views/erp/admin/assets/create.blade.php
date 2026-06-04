@extends('erp::layouts.app')

@section('title', __('Yeni Sabit Kıymet'))
@section('page-title', __('Yeni Sabit Kıymet'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.assets.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Varlık Adı')" :value="old('name')" required /></div>
                <div class="col-md-4"><x-admin-panel::input name="asset_code" :label="__('Varlık Kodu')" :value="old('asset_code')" required /></div>
                <div class="col-md-6"><x-admin-panel::input name="serial_number" :label="__('Seri No')" :value="old('serial_number')" /></div>
                <div class="col-md-6">
                    <x-admin-panel::select name="category_id" :label="__('Kategori')" required
                        :options="$categories->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('category_id')" />
                </div>
                <div class="col-md-4"><x-admin-panel::input name="purchase_date" type="date" :label="__('Satın Alma Tarihi')" :value="old('purchase_date', date('Y-m-d'))" required /></div>
                <div class="col-md-4"><x-admin-panel::input name="purchase_price" type="number" step="0.01" :label="__('Satın Alma Fiyatı')" :value="old('purchase_price')" required /></div>
                <div class="col-md-4"><x-admin-panel::input name="current_value" type="number" step="0.01" :label="__('Güncel Değer')" :value="old('current_value')" /></div>
                <div class="col-md-6">
                    <x-admin-panel::select name="assigned_to" :label="__('Atanan Çalışan')"
                        :options="$employees->map(fn($e)=>['id'=>$e->id,'name'=>$e->full_name])->pluck('name','id')->prepend(__('Seçiniz'),'')->toArray()"
                        :selected="old('assigned_to')" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="location_id" :label="__('Konum / Depo')"
                        :options="$warehouses->pluck('name','id')->prepend(__('Seçiniz'),'')->toArray()"
                        :selected="old('location_id')" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="status" :label="__('Durum')" required
                        :options="['active' => __('Aktif'), 'in_repair' => __('Onarımda'), 'disposed' => __('Elden Çıkarıldı')]"
                        :selected="old('status', 'active')" />
                </div>
                <div class="col-12"><x-admin-panel::textarea name="notes" :label="__('Notlar')" rows="2">{{ old('notes') }}</x-admin-panel::textarea></div>
            </div>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.assets.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
