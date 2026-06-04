@extends('erp::layouts.app')

@section('title', __('İş Emirleri'))
@section('page-title', __('İş Emirleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'draft' => __('Taslak'), 'released' => __('Serbest'), 'in_progress' => __('Devam Ediyor'), 'completed' => __('Tamamlandı'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.manufacturing.manage')
            <x-admin-panel::button href="{{ route('erp.work-orders.create') }}" icon="plus" variant="primary">{{ __('Yeni İş Emri') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('WO No'), __('Ürün'), __('Depo'), __('Plan. Miktar'), __('Üretilen'), __('Başlangıç'), __('Bitiş'), __('Durum'), '']">
            @forelse($orders as $wo)
                <tr>
                    <td><a href="{{ route('erp.work-orders.show', $wo) }}" class="fw-medium font-monospace">{{ $wo->wo_number }}</a></td>
                    <td>{{ $wo->product?->name }}</td>
                    <td>{{ $wo->warehouse?->name }}</td>
                    <td>{{ number_format($wo->planned_quantity,3,',','.') }}</td>
                    <td>{{ number_format($wo->produced_quantity,3,',','.') }}</td>
                    <td>{{ $erpFormat->date($wo->planned_start) }}</td>
                    <td>{{ $erpFormat->date($wo->planned_end) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($wo->status) { 'draft' => 'secondary', 'released' => 'info', 'in_progress' => 'warning', 'completed' => 'success', 'cancelled' => 'danger', default => 'secondary' } }}">
                            {{ __($wo->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.work-orders.show', $wo) }}" size="sm" variant="ghost" icon="eye" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">{{ __('İş emri bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
