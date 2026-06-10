@extends('erp::layouts.app')

@section('title', __('ERP Kurulum Sihirbazı'))
@section('page-title', __('ERP Kurulum Sihirbazı'))

@section('content')
    @php $currentStep = session('step', 1); @endphp

    @include('erp::admin.partials.status')

    {{-- Adım göstergesi --}}
    <div class="d-flex align-items-center mb-4 gap-3">
        @foreach([1 => __('Şirket Bilgileri'), 2 => __('Para & Vergi')] as $num => $label)
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                     style="width:32px;height:32px;font-size:14px;
                        background:{{ $currentStep >= $num ? '#0d6efd' : '#dee2e6' }};
                        color:{{ $currentStep >= $num ? '#fff' : '#6c757d' }}">
                    @if($currentStep > $num)
                        <i data-lucide="check" style="width:16px;height:16px;"></i>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="fw-semibold {{ $currentStep === $num ? 'text-primary' : 'text-muted' }}">{{ $label }}</span>
            </div>
            @if($num < 2)
                <div class="flex-grow-1 border-top border-2"></div>
            @endif
        @endforeach
    </div>

    <x-admin-panel::card>
        @if($currentStep === 1)
            <h5 class="fw-semibold mb-4">{{ __('Adım 1 — Şirket Bilgileri') }}</h5>

            <form method="POST" action="{{ route('erp.setup.store') }}">
                @csrf
                <input type="hidden" name="step" value="1" />

                <div class="row g-3">
                    <div class="col-md-6">
                        <x-admin-panel::input
                            name="company_name"
                            :label="__('Şirket Adı')"
                            :value="old('company_name')"
                            required
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input
                            name="company_email"
                            type="email"
                            :label="__('E-posta')"
                            :value="old('company_email')"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input
                            name="company_phone"
                            :label="__('Telefon')"
                            :value="old('company_phone')"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin-panel::input
                            name="company_address"
                            :label="__('Adres')"
                            :value="old('company_address')"
                        />
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <x-admin-panel::button type="submit" variant="primary" icon="arrow-right">
                        {{ __('Devam Et') }}
                    </x-admin-panel::button>
                </div>
            </form>

        @elseif($currentStep === 2)
            <h5 class="fw-semibold mb-4">{{ __('Adım 2 — Para Birimi & Vergi Ayarları') }}</h5>

            <form method="POST" action="{{ route('erp.setup.store') }}">
                @csrf
                <input type="hidden" name="step" value="2" />

                <div class="row g-3">
                    <div class="col-md-4">
                        <x-admin-panel::select
                            name="currency"
                            :label="__('Para Birimi')"
                            :options="['TRY' => 'TRY — Türk Lirası', 'USD' => 'USD — Amerikan Doları', 'EUR' => 'EUR — Euro']"
                            :selected="old('currency', 'TRY')"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin-panel::input
                            name="default_tax_rate"
                            type="number"
                            :label="__('Varsayılan KDV Oranı (%)')"
                            :value="old('default_tax_rate', '20')"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin-panel::input
                            name="invoice_prefix"
                            :label="__('Fatura Öneki')"
                            :value="old('invoice_prefix', 'INV')"
                            required
                        />
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('erp.setup') }}" class="btn btn-outline-secondary">
                        {{ __('Geri') }}
                    </a>
                    <x-admin-panel::button type="submit" variant="primary" icon="check">
                        {{ __('Kurulumu Tamamla') }}
                    </x-admin-panel::button>
                </div>
            </form>
        @endif
    </x-admin-panel::card>
@endsection
