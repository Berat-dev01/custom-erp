@extends('erp::layouts.app')

@section('title', __('Proje Düzenle'))
@section('page-title', __('Proje Düzenle'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.projects.update', $project) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Proje Adı')" :value="old('name', $project->name)" required /></div>
                <div class="col-md-4"><x-admin-panel::input name="code" :label="__('Proje Kodu')" :value="old('code', $project->code)" required /></div>
                <div class="col-md-6">
                    <x-admin-panel::select name="customer_id" :label="__('Müşteri')"
                        :options="$customers->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('customer_id', $project->customer_id)" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="manager_id" :label="__('Proje Yöneticisi')"
                        :options="$managers->map(fn($e) => ['id'=>$e->id,'name'=>$e->full_name])->pluck('name','id')->prepend(__('Seçiniz'), '')->toArray()"
                        :selected="old('manager_id', $project->manager_id)" />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::select name="status" :label="__('Durum')" required
                        :options="['planning' => __('Planlama'), 'active' => __('Aktif'), 'on_hold' => __('Beklemede'), 'completed' => __('Tamamlandı'), 'cancelled' => __('İptal')]"
                        :selected="old('status', $project->status)" />
                </div>
                <div class="col-md-4"><x-admin-panel::input name="start_date" type="date" :label="__('Başlangıç')" :value="old('start_date', $project->start_date?->format('Y-m-d'))" /></div>
                <div class="col-md-4"><x-admin-panel::input name="end_date" type="date" :label="__('Bitiş')" :value="old('end_date', $project->end_date?->format('Y-m-d'))" /></div>
                <div class="col-md-4"><x-admin-panel::input name="budget" type="number" step="0.01" :label="__('Bütçe')" :value="old('budget', $project->budget)" /></div>
                <div class="col-12"><x-admin-panel::textarea name="description" :label="__('Açıklama')" rows="3">{{ old('description', $project->description) }}</x-admin-panel::textarea></div>
            </div>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Güncelle') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.projects.show', $project) }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
