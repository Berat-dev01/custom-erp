@extends('erp::layouts.app')

@section('title', __('İzin Talepleri'))
@section('page-title', __('İzin Talepleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    @if($pendingCount > 0)
        <x-admin-panel::alert type="warning" class="mb-3">
            {{ __(':count bekleyen izin talebi var.', ['count' => $pendingCount]) }}
        </x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'pending' => __('Bekleyen'), 'approved' => __('Onaylı'), 'rejected' => __('Reddedildi'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::select name="employee_id"
                :options="$employees->pluck('full_name','id')->prepend(__('Tüm Çalışanlar'),''  )->toArray()"
                :selected="request('employee_id')" />
            <x-admin-panel::select name="leave_type_id"
                :options="$leaveTypes->pluck('name','id')->prepend(__('Tüm İzin Tipleri'),''  )->toArray()"
                :selected="request('leave_type_id')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.leave.create')
            <x-admin-panel::button href="{{ route('erp.leave-requests.create') }}" icon="plus" variant="primary">{{ __('Yeni Talep') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Çalışan'), __('İzin Tipi'), __('Başlangıç'), __('Bitiş'), __('Gün'), __('Durum'), '']">
            @forelse($requests as $req)
                <tr>
                    <td>
                        <div class="fw-medium">{{ $req->employee?->full_name }}</div>
                        <div class="text-muted small">{{ $req->employee?->department?->name }}</div>
                    </td>
                    <td>{{ $req->leaveType?->name }}</td>
                    <td>{{ $erpFormat->date($req->start_date) }}</td>
                    <td>{{ $erpFormat->date($req->end_date) }}</td>
                    <td>{{ $req->days }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($req->status) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'secondary' } }}">
                            {{ __($req->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end d-flex gap-1 justify-content-end">
                        @can('erp.leave.approve')
                            @if($req->isPending())
                                <form method="POST" action="{{ route('erp.leave-requests.approve', $req) }}">
                                    @csrf @method('PATCH')
                                    <x-admin-panel::button type="submit" size="sm" variant="primary" icon="check">{{ __('Onayla') }}</x-admin-panel::button>
                                </form>
                                <form method="POST" action="{{ route('erp.leave-requests.reject', $req) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="rejection_reason" value="{{ __('Onaylanmadı') }}" />
                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="x">{{ __('Reddet') }}</x-admin-panel::button>
                                </form>
                            @endif
                        @endcan
                        @if($req->isPending())
                            <form method="POST" action="{{ route('erp.leave-requests.cancel', $req) }}">
                                @csrf @method('PATCH')
                                <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="ban">{{ __('İptal') }}</x-admin-panel::button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('İzin talebi bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $requests->links() }}</div>
@endsection
