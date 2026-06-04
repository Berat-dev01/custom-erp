@extends('erp::layouts.app')

@section('title', __('Rol Yönetimi'))
@section('page-title', __('Rol Yönetimi'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <x-admin-panel::button href="{{ route('erp.roles.users') }}" variant="outline" icon="users">{{ __('Kullanıcı Rolleri') }}</x-admin-panel::button>
        <x-admin-panel::button href="{{ route('erp.roles.create') }}" icon="plus" variant="primary">{{ __('Yeni Rol') }}</x-admin-panel::button>
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Rol Adı'), __('İzin Sayısı'), __('Kullanıcı Sayısı'), __('Tür'), '']">
            @forelse($roles as $role)
                @php $isSystem = in_array($role->name, ['erp_admin','erp_hr','erp_finance','erp_inventory','erp_sales','erp_viewer']); @endphp
                <tr>
                    <td class="fw-medium">{{ $role->name }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $isSystem ? 'primary' : 'secondary' }}">
                            {{ $isSystem ? __('Sistem') : __('Özel') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.roles.edit', $role) }}" size="sm" variant="outline" icon="pencil">{{ __('Yetkiler') }}</x-admin-panel::button>
                        @if(! $isSystem)
                            <form method="POST" action="{{ route('erp.roles.destroy', $role) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Rol bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
