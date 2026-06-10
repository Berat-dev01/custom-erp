@extends('erp::layouts.app')

@section('title', __('Veri İçe Aktarma'))
@section('page-title', __('Veri İçe Aktarma'))

@section('content')
    @include('erp::admin.partials.status')

    @if(session('import_result'))
        @php $r = session('import_result'); @endphp
        <x-admin-panel::alert type="{{ empty($r['errors']) ? 'success' : 'warning' }}" dismissible class="mb-3">
            <strong>{{ $r['imported'] }} {{ __('kayıt içe aktarıldı.') }}</strong>
            @if(! empty($r['errors']))
                <ul class="mb-0 mt-1">
                    @foreach(array_slice($r['errors'], 0, 20) as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                    @if(count($r['errors']) > 20)
                        <li>... {{ count($r['errors']) - 20 }} {{ __('daha fazla hata') }}</li>
                    @endif
                </ul>
            @endif
        </x-admin-panel::alert>
    @endif

    <div class="row g-3">
        @php
            $imports = [
                ['title' => __('Çalışanlar'),    'template' => 'erp.import.template-employees',  'action' => 'erp.import.employees',    'perm' => 'erp.employees.create',       'icon' => 'users'],
                ['title' => __('Ürünler'),        'template' => 'erp.import.template-products',   'action' => 'erp.import.products',     'perm' => 'erp.products.create',        'icon' => 'package'],
                ['title' => __('Müşteriler'),     'template' => 'erp.import.template-customers',  'action' => 'erp.import.customers',    'perm' => 'erp.customers.create',       'icon' => 'building'],
                ['title' => __('Tedarikçiler'),   'template' => 'erp.import.template-suppliers',  'action' => 'erp.import.suppliers',    'perm' => 'erp.suppliers.create',       'icon' => 'truck'],
                ['title' => __('Stok Seviyeleri'),'template' => 'erp.import.template-stock',      'action' => 'erp.import.stock-levels', 'perm' => 'erp.stock_movements.create', 'icon' => 'layers'],
            ];
        @endphp

        @foreach($imports as $imp)
            @can($imp['perm'])
                <div class="col-md-4">
                    <x-admin-panel::card>
                        <h6 class="fw-semibold mb-3">
                            <i data-lucide="{{ $imp['icon'] }}" style="width:16px;height:16px;vertical-align:middle;" class="me-1"></i>
                            {{ $imp['title'] }}
                        </h6>

                        <div class="mb-3">
                            <a href="{{ route($imp['template']) }}" class="btn btn-sm btn-outline-secondary w-100">
                                <i data-lucide="download" style="width:14px;height:14px;vertical-align:middle;" class="me-1"></i>
                                {{ __('Şablonu İndir') }}
                            </a>
                        </div>

                        <form method="POST" action="{{ route($imp['action']) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="file" accept=".xlsx,.csv" class="form-control form-control-sm mb-2" required />
                            <x-admin-panel::button type="submit" variant="primary" icon="upload" class="w-100">{{ __('İçe Aktar') }}</x-admin-panel::button>
                        </form>
                    </x-admin-panel::card>
                </div>
            @endcan
        @endforeach
    </div>

    <x-admin-panel::card class="mt-4">
        <h6 class="fw-semibold mb-3">{{ __('Format Bilgisi') }}</h6>
        <ul class="text-muted small mb-0">
            <li>{{ __('Dosya formatı: XLSX veya CSV') }}</li>
            <li>{{ __('İlk satır başlık satırıdır — değiştirmeyin') }}</li>
            <li>{{ __('Tarih formatı: YYYY-MM-DD (örn: 2024-01-15)') }}</li>
            <li>{{ __('Sayısal değerlerde ondalık ayraç olarak nokta kullanın (örn: 100.50)') }}</li>
            <li>{{ __('Maksimum 10.000 satır desteklenir') }}</li>
        </ul>
    </x-admin-panel::card>
@endsection
