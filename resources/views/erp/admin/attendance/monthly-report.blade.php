@extends('erp::layouts.app')

@section('title', $employee->full_name.' — '.__('Aylık Rapor'))
@section('page-title', $employee->full_name.' — '.__('Aylık Devam Raporu'))

@section('content')
    <div class="mb-3 d-flex gap-2">
        <x-admin-panel::button href="{{ route('erp.attendance.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Çizelgeye Dön') }}
        </x-admin-panel::button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Mevcut')"   :value="(string) $summary['present']"    icon="user-check" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Devamsız')" :value="(string) $summary['absent']"     icon="user-x" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('İzinde')"   :value="(string) $summary['on_leave']"   icon="coffee" /></div>
        <div class="col-sm-3"><x-admin-panel::stat-card :label="__('Toplam Saat')" :value="number_format($summary['total_hours'], 1)" icon="clock" /></div>
    </div>

    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3">{{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}</h6>
        <x-admin-panel::table :headers="[__('Tarih'), __('Giriş'), __('Çıkış'), __('Mesai'), __('Fazla Mesai'), __('Durum')]">
            @forelse($records as $rec)
                <tr>
                    <td>{{ $erpFormat->date($rec->date) }} <span class="text-muted small">{{ $rec->date->translatedFormat('D') }}</span></td>
                    <td>{{ $rec->check_in ?? '-' }}</td>
                    <td>{{ $rec->check_out ?? '-' }}</td>
                    <td>{{ $rec->work_hours ? number_format($rec->work_hours, 1).' sa' : '-' }}</td>
                    <td>{{ $rec->overtime_hours > 0 ? number_format($rec->overtime_hours, 1).' sa' : '-' }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($rec->status) { 'present' => 'success', 'absent' => 'danger', 'on_leave' => 'warning', 'holiday' => 'info', 'half_day' => 'secondary', default => 'secondary' } }}">
                            {{ __($rec->status) }}
                        </x-admin-panel::badge>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Bu ay için devam kaydı yok.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
