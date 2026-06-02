@extends('erp::layouts.app')

@section('title', __('Projeler'))
@section('page-title', __('Projeler'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.projects.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::input name="search" placeholder="{{ __('Proje adı veya kodu...') }}" :value="request('search')" />
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'planning' => __('Planlama'), 'active' => __('Aktif'), 'on_hold' => __('Beklemede'), 'completed' => __('Tamamlandı'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.projects.create')
            <x-admin-panel::button href="{{ route('erp.projects.create') }}" icon="plus" variant="primary">{{ __('Yeni Proje') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Proje'), __('Müşteri'), __('Yönetici'), __('Süre'), __('Bütçe'), __('Görevler'), __('Durum'), '']">
            @forelse($projects as $project)
                <tr>
                    <td>
                        <a href="{{ route('erp.projects.show', $project) }}" class="fw-medium">{{ $project->name }}</a>
                        <div class="text-muted small font-monospace">{{ $project->code }}</div>
                    </td>
                    <td>{{ $project->customer?->name ?? '-' }}</td>
                    <td>{{ $project->manager?->full_name ?? '-' }}</td>
                    <td>
                        <div class="small text-muted">{{ $erpFormat->date($project->start_date) }}</div>
                        <div class="small text-muted">{{ $erpFormat->date($project->end_date) }}</div>
                    </td>
                    <td>{{ $project->budget > 0 ? $erpFormat->money($project->budget) : '-' }}</td>
                    <td>{{ $project->tasks_count }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($project->status) { 'active' => 'success', 'completed' => 'info', 'cancelled' => 'danger', 'on_hold' => 'warning', default => 'secondary' } }}">
                            {{ __($project->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.projects.update')
                            <x-admin-panel::button href="{{ route('erp.projects.edit', $project) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.projects.delete')
                            <form method="POST" action="{{ route('erp.projects.destroy', $project) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Projeyi silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Proje bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $projects->links() }}</div>
@endsection
