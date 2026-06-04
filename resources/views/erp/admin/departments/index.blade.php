@extends('erp::layouts.app')

@section('title', __('Departmanlar'))
@section('page-title', __('Departmanlar'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('erp.departments.create')
            <x-admin-panel::button href="{{ route('erp.departments.create') }}" icon="plus" variant="primary">
                {{ __('Yeni Departman') }}
            </x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Ad'), __('Kod'), __('Üst Departman'), __('Yönetici'), __('Çalışan'), __('Durum'), '']">
            @forelse($departments as $dept)
                <tr>
                    <td>{{ $dept->name }}</td>
                    <td>{{ $dept->code ?? '-' }}</td>
                    <td>{{ $dept->parent?->name ?? '-' }}</td>
                    <td>{{ $dept->manager?->full_name ?? '-' }}</td>
                    <td>{{ $dept->employees_count }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $dept->is_active ? 'success' : 'secondary' }}">
                            {{ $dept->is_active ? __('Aktif') : __('Pasif') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.departments.update')
                            <x-admin-panel::button href="{{ route('erp.departments.edit', $dept) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.departments.delete')
                            <form method="POST" action="{{ route('erp.departments.destroy', $dept) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Bu departmanı silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Departman bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    <div class="mt-3">{{ $departments->links() }}</div>
@endsection
