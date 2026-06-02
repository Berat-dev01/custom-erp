@extends('erp::layouts.app')

@section('title', __('İK Özeti Raporu'))
@section('page-title', __('İK Özeti Raporu'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.reports.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Raporlara Dön') }}
        </x-admin-panel::button>
    </div>

    {{-- Özet Kartlar --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-admin-panel::stat-card
                :label="__('Aktif Çalışan')"
                :value="(string) $total_active"
                icon="user-check"
            />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card
                :label="__('İzinde')"
                :value="(string) $total_on_leave"
                icon="coffee"
            />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card
                :label="__('Ayrılan (Toplam)')"
                :value="(string) $total_terminated"
                icon="user-x"
            />
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Departman Headcount --}}
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Departman Bazlı Headcount') }}</h6>
                <x-admin-panel::table :headers="[__('Departman'), __('Aktif Çalışan')]">
                    @forelse($department_headcount as $dept)
                        <tr>
                            <td class="fw-medium">{{ $dept->name }}</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1">
                                    {{ $dept->employees_count }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">{{ __('Departman bulunamadı.') }}</td></tr>
                    @endforelse
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>

        {{-- Çalışma Türü --}}
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Çalışma Türü Dağılımı') }}</h6>
                <x-admin-panel::table :headers="[__('Tür'), __('Sayı')]">
                    @forelse($by_employment_type as $type => $count)
                        <tr>
                            <td>{{ __((string) str($type)->replace('_', ' ')->headline()) }}</td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold px-2 py-1">
                                    {{ $count }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">{{ __('Veri yok.') }}</td></tr>
                    @endforelse
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>
    </div>

    {{-- Son İşe Başlayanlar --}}
    <x-admin-panel::card class="mb-4">
        <h6 class="fw-semibold mb-3 text-success">
            <i data-lucide="user-plus" style="width:16px;height:16px;vertical-align:middle;" class="me-1"></i>
            {{ __('Son 30 Günde İşe Başlayanlar') }}
        </h6>
        <x-admin-panel::table :headers="[__('Çalışan'), __('Departman'), __('Pozisyon'), __('İşe Başlama')]">
            @forelse($recent_hires as $emp)
                <tr>
                    <td>
                        @can('erp.employees.view')
                            <a href="{{ route('erp.employees.show', $emp) }}" class="fw-medium">{{ $emp->full_name }}</a>
                        @else
                            <span class="fw-medium">{{ $emp->full_name }}</span>
                        @endcan
                    </td>
                    <td>{{ $emp->department?->name ?? '-' }}</td>
                    <td>{{ $emp->position?->name ?? '-' }}</td>
                    <td>{{ $erpFormat->date($emp->hire_date) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Son 30 günde işe başlayan yok.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    {{-- Son Ayrılanlar --}}
    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3 text-danger">
            <i data-lucide="user-minus" style="width:16px;height:16px;vertical-align:middle;" class="me-1"></i>
            {{ __('Son 30 Günde Ayrılanlar') }}
        </h6>
        <x-admin-panel::table :headers="[__('Çalışan'), __('Departman'), __('Pozisyon'), __('Ayrılma Tarihi')]">
            @forelse($recent_terminations as $emp)
                <tr>
                    <td>
                        @can('erp.employees.view')
                            <a href="{{ route('erp.employees.show', $emp) }}" class="fw-medium">{{ $emp->full_name }}</a>
                        @else
                            <span class="fw-medium">{{ $emp->full_name }}</span>
                        @endcan
                    </td>
                    <td>{{ $emp->department?->name ?? '-' }}</td>
                    <td>{{ $emp->position?->name ?? '-' }}</td>
                    <td>{{ $erpFormat->date($emp->termination_date) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Son 30 günde ayrılan yok.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
