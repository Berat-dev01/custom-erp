@extends('erp::layouts.app')

@section('title', __('Yeni Rol'))
@section('page-title', __('Yeni Rol Oluştur'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('Rollere Dön') }}</x-admin-panel::button>
    </div>

    <form method="POST" action="{{ route('erp.roles.store') }}">
        @csrf
        <x-admin-panel::card class="mb-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::input name="name" :label="__('Rol Adı')" :value="old('name')" placeholder="erp_custom_role" required />
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <h6 class="fw-semibold mb-4">{{ __('Yetkiler') }}</h6>
            <div style="overflow-x:auto;">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:140px;">{{ __('Modül') }}</th>
                            @foreach(['view','create','update','delete','export','approve','manage'] as $action)
                                <th class="text-center small px-2">{{ ucfirst($action) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $module => $perms)
                            <tr>
                                <td class="fw-medium text-capitalize">{{ str_replace('_', ' ', $module) }}</td>
                                @foreach(['view','create','update','delete','export','approve','manage'] as $action)
                                    @php
                                        $permName = "erp.{$module}.{$action}";
                                        $exists   = collect($perms)->firstWhere('name', $permName);
                                    @endphp
                                    <td class="text-center">
                                        @if($exists)
                                            <input type="checkbox" name="permissions[]" value="{{ $permName }}" class="form-check-input" />
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <x-admin-panel::button href="{{ route('erp.roles.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Oluştur') }}</x-admin-panel::button>
            </div>
        </x-admin-panel::card>
    </form>
@endsection
