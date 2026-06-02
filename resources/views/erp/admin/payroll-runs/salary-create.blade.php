@extends('erp::layouts.app')

@section('title', __('Maaş Tanımla') . ' — ' . $employee->full_name)
@section('page-title', __('Maaş Tanımla'))

@section('content')
    <div class="row g-3">
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Yeni Maaş Tanımı') }}</h6>
                <form method="POST" action="{{ route('erp.employees.salary.store', $employee) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <x-admin-panel::input name="basic_salary" type="number" step="0.01"
                                :label="__('Brüt Maaş')" :value="old('basic_salary')" required />
                        </div>
                        <div class="col-12">
                            <x-admin-panel::input name="effective_from" type="date"
                                :label="__('Geçerlilik Başlangıcı')" :value="old('effective_from', date('Y-m-d'))" required />
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
                    @endif

                    <div class="mt-4 d-flex gap-2">
                        <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
                        <x-admin-panel::button href="{{ route('erp.employees.show', $employee) }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
                    </div>
                </form>
            </x-admin-panel::card>
        </div>

        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Maaş Geçmişi') }}</h6>
                @forelse($salaries as $sal)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <div>
                            <span class="fw-medium">{{ $erpFormat->money($sal->basic_salary) }}</span>
                            <div class="text-muted small">{{ $erpFormat->date($sal->effective_from) }} → {{ $sal->effective_to ? $erpFormat->date($sal->effective_to) : __('Devam ediyor') }}</div>
                        </div>
                        @if(!$sal->effective_to)
                            <x-admin-panel::badge variant="success">{{ __('Aktif') }}</x-admin-panel::badge>
                        @endif
                    </div>
                @empty
                    <p class="text-muted">{{ __('Henüz maaş tanımı yok.') }}</p>
                @endforelse
            </x-admin-panel::card>
        </div>
    </div>
@endsection
