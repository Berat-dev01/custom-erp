@extends('erp::layouts.app')

@section('title', $workOrder->wo_number)
@section('page-title', $workOrder->wo_number)

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.work-orders.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('İş Emirlerine Dön') }}</x-admin-panel::button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Ürün')"    :value="$workOrder->product?->name ?? '-'" icon="package" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Planlanan')" :value="number_format($workOrder->planned_quantity,3,',','.')" icon="clipboard-list" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Üretilen')"  :value="number_format($workOrder->produced_quantity,3,',','.')" icon="check-circle" /></div>
        <div class="col-sm-3">
            <x-admin-panel::stat-card :label="__('Durum')" :value="__($workOrder->status)" icon="activity" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Detaylar') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Depo') }}</th><td>{{ $workOrder->warehouse?->name }}</td></tr>
                    <tr><th>{{ __('BOM') }}</th><td>{{ $workOrder->bom?->product?->name }} v{{ $workOrder->bom?->version }}</td></tr>
                    <tr><th>{{ __('Plan Başlangıç') }}</th><td>{{ $erpFormat->date($workOrder->planned_start) }}</td></tr>
                    <tr><th>{{ __('Plan Bitiş') }}</th><td>{{ $erpFormat->date($workOrder->planned_end) }}</td></tr>
                    @if($workOrder->actual_start)
                        <tr><th>{{ __('Gerçek Başlangıç') }}</th><td>{{ $erpFormat->date($workOrder->actual_start) }}</td></tr>
                    @endif
                    @if($workOrder->actual_end)
                        <tr><th>{{ __('Gerçek Bitiş') }}</th><td>{{ $erpFormat->date($workOrder->actual_end) }}</td></tr>
                    @endif
                </table>

                @can('erp.manufacturing.manage')
                    <div class="d-flex flex-column gap-2 mt-3">
                        @if($workOrder->isDraft())
                            <form method="POST" action="{{ route('erp.work-orders.release', $workOrder) }}">
                                @csrf @method('PATCH')
                                <x-admin-panel::button type="submit" variant="primary" icon="play" class="w-100">{{ __('Serbest Bırak') }}</x-admin-panel::button>
                            </form>
                        @endif

                        @if($workOrder->isActive())
                            <form method="POST" action="{{ route('erp.work-orders.complete', $workOrder) }}" class="d-flex gap-2">
                                @csrf @method('PATCH')
                                <input type="number" name="produced_quantity" step="0.001" min="0.001"
                                    value="{{ $workOrder->planned_quantity }}"
                                    class="form-control form-control-sm" style="width:120px" />
                                <x-admin-panel::button type="submit" variant="success" icon="check" class="flex-grow-1">{{ __('Tamamla') }}</x-admin-panel::button>
                            </form>
                        @endif

                        @if(! in_array($workOrder->status, ['completed', 'cancelled']))
                            <form method="POST" action="{{ route('erp.work-orders.cancel', $workOrder) }}">
                                @csrf @method('PATCH')
                                <x-admin-panel::button type="submit" variant="danger" icon="x-circle" class="w-100"
                                    onclick="return confirm('{{ __('İptal etmek istediğinize emin misiniz?') }}')">{{ __('İptal Et') }}</x-admin-panel::button>
                            </form>
                        @endif
                    </div>
                @endcan
            </x-admin-panel::card>
        </div>

        <div class="col-md-8">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Hammadde Tüketimi') }}</h6>
                <x-admin-panel::table :headers="[__('Bileşen'), __('Plan. Miktar'), __('Gerçek Miktar'), __('Fark')]">
                    @forelse($workOrder->consumptions as $c)
                        @php $diff = (float)$c->actual_quantity - (float)$c->planned_quantity; @endphp
                        <tr>
                            <td>{{ $c->product?->name }}</td>
                            <td>{{ number_format($c->planned_quantity,3,',','.') }} {{ $c->product?->unit?->abbreviation }}</td>
                            <td>{{ number_format($c->actual_quantity,3,',','.') }}</td>
                            <td class="{{ $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-warning' : 'text-success') }}">
                                {{ $diff != 0 ? ($diff > 0 ? '+' : '').number_format($diff,3,',','.') : '—' }}
                            </td>
                        </tr>
                    @empty
                        @foreach($workOrder->bom?->components ?? [] as $comp)
                            @php $scale = (float)$workOrder->planned_quantity / max((float)$workOrder->bom->quantity,0.001); @endphp
                            <tr>
                                <td>{{ $comp->component?->name }}</td>
                                <td>{{ number_format((float)$comp->quantity * $scale,3,',','.') }} {{ $comp->component?->unit?->abbreviation }}</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                            </tr>
                        @endforeach
                    @endforelse
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
