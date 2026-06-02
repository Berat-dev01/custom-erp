@extends('erp::layouts.app')

@section('title', __('Raporlar'))
@section('page-title', __('Raporlar'))

@section('content')
    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('erp.reports.revenue') }}" class="text-decoration-none">
                <x-admin-panel::card>
                    <div class="d-flex align-items-center gap-3 p-2">
                        <div style="width:48px;height:48px;border-radius:12px;background:var(--color-primary-100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="trending-up" style="width:24px;height:24px;color:var(--color-primary-600);"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ __('Gelir / Gider') }}</div>
                            <div class="text-muted small">{{ __('Son 12 ay gelir ve gider analizi') }}</div>
                        </div>
                    </div>
                </x-admin-panel::card>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('erp.reports.inventory') }}" class="text-decoration-none">
                <x-admin-panel::card>
                    <div class="d-flex align-items-center gap-3 p-2">
                        <div style="width:48px;height:48px;border-radius:12px;background:var(--color-success-100,#dcfce7);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="package" style="width:24px;height:24px;color:#16a34a;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ __('Stok Değeri') }}</div>
                            <div class="text-muted small">{{ __('Depo bazlı stok özeti ve düşük stok') }}</div>
                        </div>
                    </div>
                </x-admin-panel::card>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('erp.reports.hr') }}" class="text-decoration-none">
                <x-admin-panel::card>
                    <div class="d-flex align-items-center gap-3 p-2">
                        <div style="width:48px;height:48px;border-radius:12px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="users" style="width:24px;height:24px;color:#7c3aed;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ __('İK Özeti') }}</div>
                            <div class="text-muted small">{{ __('Departman headcount, işe giriş/çıkış') }}</div>
                        </div>
                    </div>
                </x-admin-panel::card>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('erp.reports.aging') }}" class="text-decoration-none">
                <x-admin-panel::card>
                    <div class="d-flex align-items-center gap-3 p-2">
                        <div style="width:48px;height:48px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="clock" style="width:24px;height:24px;color:#d97706;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ __('Yaşlandırma') }}</div>
                            <div class="text-muted small">{{ __('Vadesi geçmiş alacaklar analizi') }}</div>
                        </div>
                    </div>
                </x-admin-panel::card>
            </a>
        </div>
    </div>
@endsection
