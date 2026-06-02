@extends('erp::layouts.app')

@section('title', __('Çalışanlar'))
@section('page-title', __('Çalışanlar'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.employees.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::input name="search" placeholder="{{ __('Ad, soyad, e-posta, sicil no...') }}" :value="request('search')" />
            <x-admin-panel::select name="department_id"
                :options="$departments->pluck('name','id')->prepend(__('Tüm Departmanlar'), '')->toArray()"
                :selected="request('department_id')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'active' => __('Aktif'), 'on_leave' => __('İzinde'), 'terminated' => __('Ayrılmış')]"
                :selected="request('status')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>

        @can('erp.employees.create')
            <x-admin-panel::button href="{{ route('erp.employees.create') }}" icon="plus" variant="primary">
                {{ __('Yeni Çalışan') }}
            </x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Sicil No'), __('Ad Soyad'), __('Departman'), __('Pozisyon'), __('İstihdam'), __('Durum'), '']">
            @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_number }}</td>
                    <td>
                        <a href="{{ route('erp.employees.show', $employee) }}" class="fw-medium">
                            {{ $employee->full_name }}
                        </a>
                        <div class="text-muted small">{{ $employee->email }}</div>
                    </td>
                    <td>{{ $employee->department?->name ?? '-' }}</td>
                    <td>{{ $employee->position?->name ?? '-' }}</td>
                    <td>
                        <x-admin-panel::badge variant="secondary">{{ __($employee->employment_type) }}</x-admin-panel::badge>
                    </td>
                    <td>
                        <x-admin-panel::badge variant="{{ $employee->status === 'active' ? 'success' : ($employee->status === 'on_leave' ? 'warning' : 'danger') }}">
                            {{ __($employee->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.employees.update')
                            <x-admin-panel::button href="{{ route('erp.employees.edit', $employee) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.employees.delete')
                            <form method="POST" action="{{ route('erp.employees.destroy', $employee) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Bu çalışanı silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Çalışan bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    <div class="mt-3">{{ $employees->links() }}</div>
@endsection
