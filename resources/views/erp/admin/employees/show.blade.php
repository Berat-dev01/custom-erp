@extends('erp::layouts.app')

@section('title', $employee->full_name)
@section('page-title', $employee->full_name)

@section('content')
    @include('erp::admin.partials.status')

    <div class="d-flex gap-2 mb-3">
        @can('erp.employees.update')
            <x-admin-panel::button href="{{ route('erp.employees.edit', $employee) }}" icon="pencil" variant="outline">{{ __('Düzenle') }}</x-admin-panel::button>
        @endcan
        <x-admin-panel::button href="{{ route('erp.employees.index') }}" variant="ghost">{{ __('← Listeye Dön') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Genel Bilgiler') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Sicil No') }}</th><td>{{ $employee->employee_number }}</td></tr>
                    <tr><th>{{ __('Ad Soyad') }}</th><td>{{ $employee->full_name }}</td></tr>
                    <tr><th>{{ __('E-posta') }}</th><td>{{ $employee->email }}</td></tr>
                    <tr><th>{{ __('Telefon') }}</th><td>{{ $employee->phone ?? '-' }}</td></tr>
                    <tr><th>{{ __('TC Kimlik') }}</th><td>{{ $employee->national_id ?? '-' }}</td></tr>
                    <tr><th>{{ __('Doğum Tarihi') }}</th><td>{{ $erpFormat->date($employee->birth_date) }}</td></tr>
                    <tr><th>{{ __('Cinsiyet') }}</th><td>{{ __($employee->gender ?? '-') }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('İş Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Departman') }}</th><td>{{ $employee->department?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Pozisyon') }}</th><td>{{ $employee->position?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Yönetici') }}</th><td>{{ $employee->manager?->full_name ?? '-' }}</td></tr>
                    <tr><th>{{ __('İşe Giriş') }}</th><td>{{ $erpFormat->date($employee->hire_date) }}</td></tr>
                    <tr><th>{{ __('İstihdam Türü') }}</th><td>{{ __($employee->employment_type) }}</td></tr>
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ $employee->status === 'active' ? 'success' : ($employee->status === 'on_leave' ? 'warning' : 'danger') }}">
                            {{ __($employee->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                </table>
            </x-admin-panel::card>
        </div>

        @if($employee->documents->isNotEmpty())
            <div class="col-12">
                <x-admin-panel::card>
                    <h6 class="fw-semibold mb-3">{{ __('Belgeler') }}</h6>
                    <x-admin-panel::table :headers="[__('Belge Adı'), __('Tür'), __('Son Geçerlilik')]">
                        @foreach($employee->documents as $doc)
                            <tr>
                                <td>{{ $doc->name }}</td>
                                <td>{{ __($doc->type) }}</td>
                                <td>{{ $erpFormat->date($doc->expiry_date) }}</td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                </x-admin-panel::card>
            </div>
        @endif
    </div>
@endsection
