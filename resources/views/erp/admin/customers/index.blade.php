@extends('erp::layouts.app')

@section('title', __('Müşteriler'))
@section('page-title', __('Müşteriler'))

@section('content')
    @php
        $activeFilterCount = collect($filters)
            ->except(['sort', 'direction'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input" aria-label="'.e(__('Tümünü seç')).'">'), 'width' => '36px'],
            ['label' => __('Ad')],
            ['label' => __('E-posta')],
            ['label' => __('Telefon')],
            ['label' => __('Ödeme Vadesi')],
            ['label' => __('Kredi Limiti')],
            ['label' => __('Durum')],
            ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="erp-customers">
        @include('erp::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('ERP') }}</p>
                <h1>{{ __('Müşteriler') }}</h1>
            </div>

            <div class="crm-admin-actions">
                @can('erp.customers.export')
                    <x-admin-panel::export-button
                        :url="route('erp.customers.export')"
                        :columns="$exportColumns"
                        :formats="$exportFormats"
                        module="erp-customers"
                    />
                @endcan
                @can('erp.customers.create')
                    <x-admin-panel::button :href="route('erp.customers.create')" icon="plus">
                        {{ __('Yeni Müşteri') }}
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="erp-customers-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.customers.index')" :reset-url="route('erp.customers.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Ara" :value="$filters['search']" placeholder="Ad, e-posta, vergi no..." />
                    <x-admin-panel::select name="status" label="Durum"
                        :options="['active' => __('Aktif'), 'inactive' => __('Pasif')]"
                        :selected="$filters['status']" placeholder="Tüm durumlar" />
                </x-slot:compact>

                <x-slot:advanced>
                    <x-admin-panel::select
                        name="sort"
                        label="Sırala"
                        :selected="$filters['sort']"
                        :options="[
                            'created_at'         => __('Eklenme tarihi'),
                            'name'               => __('Ad'),
                            'payment_terms_days' => __('Ödeme vadesi'),
                            'credit_limit'       => __('Kredi limiti'),
                        ]"
                    />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>

            <form id="erp-customers-bulk" method="POST" action="{{ route('erp.customers.bulk-delete') }}">
                @csrf
                @method('DELETE')

                <x-admin-panel::bulk-actions form="erp-customers-bulk" checkbox-selector=".erp-customer-selector" label="müşteri">
                    @can('erp.customers.delete')
                        <x-admin-panel::button
                            type="submit"
                            size="sm"
                            variant="danger"
                            icon="trash-2"
                            form="erp-customers-bulk"
                            data-admin-confirm="{{ __('Seçili müşterileri silmek istediğinize emin misiniz?') }}"
                        >
                            {{ __('Seçilenleri Sil') }}
                        </x-admin-panel::button>
                    @endcan
                </x-admin-panel::bulk-actions>

                <x-admin-panel::card>
                    <x-slot:header>{{ __('Müşteriler') }}</x-slot:header>

                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    name="record_ids[]"
                                    value="{{ $customer->id }}"
                                    class="form-check-input erp-customer-selector"
                                >
                            </td>
                            <td>
                                <a href="{{ route('erp.customers.show', $customer) }}" class="fw-medium">{{ $customer->name }}</a>
                                @if($customer->contact_person)
                                    <div class="crm-muted">{{ $customer->contact_person }}</div>
                                @endif
                            </td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>{{ $customer->payment_terms_days }} {{ __('gün') }}</td>
                            <td>{{ $erpFormat->money($customer->credit_limit) }}</td>
                            <td>
                                <x-admin-panel::badge variant="{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $customer->status === 'active' ? __('Aktif') : __('Pasif') }}
                                </x-admin-panel::badge>
                            </td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('erp.customers.show', $customer)" size="sm" variant="ghost" icon="eye" />
                                    @can('erp.customers.update')
                                        <x-admin-panel::button :href="route('erp.customers.edit', $customer)" size="sm" variant="ghost" icon="pencil" />
                                    @endcan
                                    @can('erp.customers.delete')
                                        <x-admin-panel::button
                                            type="submit"
                                            size="sm"
                                            variant="danger"
                                            icon="trash-2"
                                            form="erp-customer-delete-{{ $customer->id }}"
                                            data-admin-confirm="{{ __('Bu müşteriyi silmek istediğinize emin misiniz?') }}"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                @include('erp::admin.partials.empty-state', [
                                    'title'            => __('Müşteri bulunamadı.'),
                                    'body'             => __('Yeni müşteri ekleyin veya filtreleri sıfırlayın.'),
                                    'actionUrl'        => route('erp.customers.create'),
                                    'actionLabel'      => __('Yeni Müşteri'),
                                    'actionPermission' => 'erp.customers.create',
                                ])
                            </td>
                        </tr>
                    @endforelse
                    </x-admin-panel::table>

                    <span hidden data-export-total="{{ $customers->total() }}"></span>

                    <x-admin-panel::pagination :paginator="$customers" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>

        @foreach($customers as $customer)
            @can('erp.customers.delete')
                <form id="erp-customer-delete-{{ $customer->id }}" method="POST" action="{{ route('erp.customers.destroy', $customer) }}" class="crm-hidden-form">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        @endforeach
    </section>
@endsection
