@extends('erp::layouts.app')

@section('title', __('Yeni İzin Talebi'))
@section('page-title', __('Yeni İzin Talebi'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.leave-requests.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Taleplere Dön') }}
        </x-admin-panel::button>
    </div>

    <form method="POST" action="{{ route('erp.leave-requests.store') }}">
        @csrf
        <x-admin-panel::card>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::select name="employee_id" :label="__('Çalışan')"
                        :options="$employees->map(fn($e) => ['id' => $e->id, 'name' => $e->full_name.' ('.$e->employee_number.')'])->pluck('name','id')->toArray()"
                        :selected="old('employee_id')" required />
                    @error('employee_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="leave_type_id" :label="__('İzin Tipi')"
                        :options="$leaveTypes->pluck('name','id')->toArray()"
                        :selected="old('leave_type_id')" required />
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="start_date" :label="__('Başlangıç Tarihi')" type="date" :value="old('start_date')" required />
                    @error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <x-admin-panel::input name="end_date" :label="__('Bitiş Tarihi')" type="date" :value="old('end_date')" required />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="reason" :label="__('Açıklama (opsiyonel)')" :value="old('reason')" rows="2" />
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <x-admin-panel::button href="{{ route('erp.leave-requests.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
                <x-admin-panel::button type="submit" variant="primary" icon="send">{{ __('Talep Gönder') }}</x-admin-panel::button>
            </div>
        </x-admin-panel::card>
    </form>
@endsection
