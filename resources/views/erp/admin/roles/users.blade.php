@extends('erp::layouts.app')

@section('title', __('Kullanıcı Rolleri'))
@section('page-title', __('Kullanıcı Rolleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.roles.index') }}" variant="ghost" icon="arrow-left" size="sm">{{ __('Rollere Dön') }}</x-admin-panel::button>
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Kullanıcı'), __('E-posta'), __('Mevcut Roller'), __('Rol Ata'), __('Rol Kaldır')]">
            @forelse($users as $user)
                <tr>
                    <td class="fw-medium">{{ $user->name }}</td>
                    <td class="text-muted small">{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles->where('guard_name', 'web') as $role)
                            <x-admin-panel::badge variant="primary" class="me-1">{{ $role->name }}</x-admin-panel::badge>
                        @endforeach
                    </td>
                    <td>
                        <form method="POST" action="{{ route('erp.roles.assign', $user) }}" class="d-flex gap-1">
                            @csrf
                            <select name="role" class="form-select form-select-sm" style="width:180px">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <x-admin-panel::button type="submit" size="sm" variant="primary" icon="plus" />
                        </form>
                    </td>
                    <td>
                        @foreach($user->roles->where('guard_name', 'web') as $role)
                            <form method="POST" action="{{ route('erp.roles.remove', $user) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="role" value="{{ $role->name }}" />
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="x">{{ $role->name }}</x-admin-panel::button>
                            </form>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Kullanıcı bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $users->links() }}</div>
@endsection
