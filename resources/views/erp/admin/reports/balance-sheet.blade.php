@extends('erp::layouts.app')

@section('title', __('Bilanço'))
@section('page-title', __('Bilanço'))

@section('content')
    <form method="GET" class="d-flex gap-2 mb-4 align-items-end">
        <x-admin-panel::input name="date" type="date" :label="__('Tarih')" :value="$date->format('Y-m-d')" />
        <div class="pb-1"><x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Uygula') }}</x-admin-panel::button></div>
    </form>

    <h6 class="fw-semibold mb-3">{{ __('Bilanço') }} — {{ $erpFormat->date($date) }}</h6>

    <div class="row g-3">
        {{-- AKTİF --}}
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-bold text-primary mb-3">{{ __('AKTİF (VARLIKLAR)') }}</h6>
                <x-admin-panel::table :headers="[__('Hesap'), __('Tutar')]">
                    @forelse($data['asset'] as $row)
                        <tr>
                            <td><span class="text-muted small font-monospace me-2">{{ $row['code'] }}</span>{{ $row['name'] }}</td>
                            <td class="text-end">{{ $erpFormat->money($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">{{ __('Varlık hareketi yok.') }}</td></tr>
                    @endforelse
                    <tr class="fw-bold border-top">
                        <td>{{ __('Toplam Varlık') }}</td>
                        <td class="text-end">{{ $erpFormat->money($data['totals']['total_assets']) }}</td>
                    </tr>
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>

        {{-- PASİF --}}
        <div class="col-md-6">
            <x-admin-panel::card class="mb-3">
                <h6 class="fw-bold text-warning mb-3">{{ __('PASİF (YÜKÜMLÜLÜKLER)') }}</h6>
                <x-admin-panel::table :headers="[__('Hesap'), __('Tutar')]">
                    @forelse($data['liability'] as $row)
                        <tr>
                            <td><span class="text-muted small font-monospace me-2">{{ $row['code'] }}</span>{{ $row['name'] }}</td>
                            <td class="text-end">{{ $erpFormat->money($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">{{ __('Yükümlülük hareketi yok.') }}</td></tr>
                    @endforelse
                    <tr class="fw-bold border-top">
                        <td>{{ __('Toplam Yükümlülük') }}</td>
                        <td class="text-end">{{ $erpFormat->money($data['totals']['total_liabilities']) }}</td>
                    </tr>
                </x-admin-panel::table>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <h6 class="fw-bold text-success mb-3">{{ __('ÖZKAYNAKLAR') }}</h6>
                <x-admin-panel::table :headers="[__('Hesap'), __('Tutar')]">
                    @forelse($data['equity'] as $row)
                        <tr>
                            <td><span class="text-muted small font-monospace me-2">{{ $row['code'] }}</span>{{ $row['name'] }}</td>
                            <td class="text-end">{{ $erpFormat->money($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">{{ __('Özkaynak hareketi yok.') }}</td></tr>
                    @endforelse
                    <tr class="fw-bold border-top">
                        <td>{{ __('Toplam Özkaynak') }}</td>
                        <td class="text-end">{{ $erpFormat->money($data['totals']['total_equity']) }}</td>
                    </tr>
                </x-admin-panel::table>
            </x-admin-panel::card>

            <x-admin-panel::card class="mt-3">
                <div class="d-flex justify-content-between fw-bold">
                    <span>{{ __('Toplam Pasif') }}</span>
                    <span>{{ $erpFormat->money($data['totals']['total_liabilities'] + $data['totals']['total_equity']) }}</span>
                </div>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
