@extends('admin-panel::layouts.app')


@push('sidebar-nav')
    @if(!empty($erpNavigationGroups ?? []))
        <div class="sidebar-section-label">{{ __('ERP') }}</div>
        @foreach(($erpNavigationGroups ?? []) as $group)
            @php
                $visibleItems = collect($group['items'])
                    ->filter(fn ($item) =>
                        \Illuminate\Support\Facades\Route::has($item['route']) &&
                        \Illuminate\Support\Facades\Gate::allows($item['permission'])
                    )
                    ->values();
            @endphp

            @if($visibleItems->isNotEmpty())
                <x-admin-panel::sidebar-dropdown
                    :label="$group['label']"
                    :icon="$group['icon']"
                    :open="$group['active']"
                    storage-key="sidebar-dropdown-erp-{{ str($group['label'])->slug() }}"
                >
                    @foreach($visibleItems as $item)
                        <x-admin-panel::sidebar-item
                            :route="$item['route']"
                            :label="$item['label']"
                            :active="$item['active']"
                        />
                    @endforeach
                </x-admin-panel::sidebar-dropdown>
            @endif
        @endforeach
    @endif
@endpush
