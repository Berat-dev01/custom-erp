@extends('erp::layouts.app')

@section('title', __('Devam Çizelgesi'))
@section('page-title', __('Devam Çizelgesi'))

@section('content')
    @include('erp::admin.partials.status')

    <form method="GET" class="d-flex gap-2 mb-4 align-items-end flex-wrap">
        <div>
            <label class="form-label small">{{ __('Yıl') }}</label>
            <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" min="2020" max="2030" style="width:90px" />
        </div>
        <x-admin-panel::select name="month"
            :options="collect(range(1,12))->mapWithKeys(fn($m) => [$m => \Carbon\Carbon::create(null,$m)->translatedFormat('F')])->toArray()"
            :selected="$month" />
        <div class="pb-1"><x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Uygula') }}</x-admin-panel::button></div>
    </form>

    <x-admin-panel::card>
        <div style="overflow-x:auto;">
            <table class="table table-sm table-bordered" style="min-width:900px;">
                <thead>
                    <tr>
                        <th>{{ __('Çalışan') }}</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php $date = \Carbon\Carbon::create($year, $month, $d); @endphp
                            <th class="text-center small px-1 {{ $date->isWeekend() ? 'bg-light text-muted' : '' }}" title="{{ $date->format('D') }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th>{{ __('Özet') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        @php
                            $records = $employee->attendance->keyBy(fn($a) => $a->date->day);
                            $presentCount = $records->where('status','present')->count();
                            $absentCount  = $records->where('status','absent')->count();
                            $leaveCount   = $records->where('status','on_leave')->count();
                        @endphp
                        <tr>
                            <td class="fw-medium" style="white-space:nowrap">
                                <a href="{{ route('erp.attendance.monthly-report', $employee) }}?year={{ $year }}&month={{ $month }}" class="text-decoration-none">
                                    {{ $employee->full_name }}
                                </a>
                            </td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $date = \Carbon\Carbon::create($year, $month, $d);
                                    $rec  = $records->get($d);
                                    $isWeekend = $date->isWeekend();
                                    $statusColor = match($rec?->status) {
                                        'present'  => 'success',
                                        'absent'   => 'danger',
                                        'on_leave' => 'warning',
                                        'holiday'  => 'info',
                                        'half_day' => 'secondary',
                                        default    => null,
                                    };
                                    $label = match($rec?->status) {
                                        'present'  => 'P',
                                        'absent'   => 'A',
                                        'on_leave' => 'İ',
                                        'holiday'  => 'T',
                                        'half_day' => 'Y',
                                        default    => $isWeekend ? '—' : '',
                                    };
                                @endphp
                                <td class="text-center px-1 {{ $isWeekend ? 'bg-light' : '' }}">
                                    @if($statusColor)
                                        <span class="badge bg-{{ $statusColor }} px-1">{{ $label }}</span>
                                    @else
                                        <span class="text-muted">{{ $label }}</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="small text-muted" style="white-space:nowrap">
                                <span class="text-success">{{ $presentCount }}P</span>
                                @if($absentCount > 0)<span class="text-danger ms-1">{{ $absentCount }}A</span>@endif
                                @if($leaveCount > 0)<span class="text-warning ms-1">{{ $leaveCount }}İ</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-2 text-muted small">
            P={{ __('Mevcut') }}, A={{ __('Devamsız') }}, İ={{ __('İzinde') }}, T={{ __('Tatil') }}, Y={{ __('Yarım Gün') }}
        </div>
    </x-admin-panel::card>
    <div class="mt-3">{{ $employees->links() }}</div>
@endsection
