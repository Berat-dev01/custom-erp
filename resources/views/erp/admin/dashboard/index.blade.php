@extends('erp::layouts.app')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))

@section('content')
    <x-admin-panel::card>
        <div style="text-align: center; padding: var(--space-12) var(--space-6);">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: var(--space-2);">
                {{ __('ERP Sistemine Hoş Geldiniz') }}
            </h2>
            <p style="color: var(--color-text-muted);">
                {{ config('erp.company_name', 'Company') }}
            </p>
        </div>
    </x-admin-panel::card>
@endsection
