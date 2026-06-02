@extends('erp::layouts.app')

@section('title', __('Pozisyonlar'))
@section('page-title', __('Pozisyonlar'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('erp.positions.create')
            <x-admin-panel::button href="{{ route('erp.positions.create') }}" icon="plus" variant="primary">
                {{ __('Yeni Pozisyon') }}
            </x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Pozisyon'), __('Departman'), __('Seviye'), __('Çalışan Sayısı'), __('Durum'), '']">
            @forelse($positions as $position)
                <tr>
                    <td>{{ $position->name }}</td>
                    <td>{{ $position->department?->name ?? '-' }}</td>
                    <td>
                        <x-admin-panel::badge variant="secondary">{{ __($position->level) }}</x-admin-panel::badge>
                    </td>
                    <td>{{ $position->employees_count }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $position->is_active ? 'success' : 'secondary' }}">
                            {{ $position->is_active ? __('Aktif') : __('Pasif') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.positions.update')
                            <x-admin-panel::button href="{{ route('erp.positions.edit', $position) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.positions.delete')
                            <form method="POST" action="{{ route('erp.positions.destroy', $position) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Bu pozisyonu silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Pozisyon bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    <div class="mt-3">{{ $positions->links() }}</div>
@endsection
