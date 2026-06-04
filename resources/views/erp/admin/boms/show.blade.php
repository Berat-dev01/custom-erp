@extends('erp::layouts.app')

@section('title', $bom->product?->name.' BOM')
@section('page-title', $bom->product?->name.' — v'.$bom->version)

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.boms.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('BOM Listesine Dön') }}</x-admin-panel::button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Mamul')"        :value="$bom->product?->name ?? '-'" icon="package" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Versiyon')"     :value="'v'.$bom->version" icon="git-branch" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Üretim Miktarı')" :value="number_format($bom->quantity,3,',','.')" icon="layers" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Hammadde Maliyeti')" :value="$erpFormat->money($cost)" icon="dollar-sign" /></div>
    </div>

    <x-admin-panel::card class="mb-3">
        <h6 class="fw-semibold mb-3">{{ __('Bileşenler') }}</h6>
        <x-admin-panel::table :headers="[__('SKU'), __('Bileşen'), __('Birim'), __('Miktar'), __('Birim Maliyet'), __('Toplam Maliyet')]">
            @forelse($bom->components as $comp)
                <tr>
                    <td class="font-monospace small">{{ $comp->component?->sku }}</td>
                    <td class="fw-medium">{{ $comp->component?->name }}</td>
                    <td>{{ $comp->component?->unit?->abbreviation }}</td>
                    <td>{{ number_format($comp->quantity, 3, ',', '.') }}</td>
                    <td>{{ $erpFormat->money($comp->component?->purchase_price ?? 0) }}</td>
                    <td>{{ $erpFormat->money((float)$comp->quantity * (float)($comp->component?->purchase_price ?? 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Bileşen yok.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    @can('erp.manufacturing.manage')
        <div class="d-flex gap-2">
            <x-admin-panel::button href="{{ route('erp.work-orders.create', ['bom_id' => $bom->id]) }}" icon="play" variant="primary">{{ __('İş Emri Oluştur') }}</x-admin-panel::button>
        </div>
    @endcan
@endsection
