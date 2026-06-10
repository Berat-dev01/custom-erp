@extends('erp::layouts.app')

@section('title', $project->name)
@section('page-title', $project->name)

@section('content')
    @include('erp::admin.partials.status')

    <div class="d-flex gap-2 mb-3">
        @can('erp.projects.update')
            <x-admin-panel::button href="{{ route('erp.projects.edit', $project) }}" icon="pencil" variant="outline">{{ __('Düzenle') }}</x-admin-panel::button>
        @endcan
        <x-admin-panel::button href="{{ route('erp.projects.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    {{-- Özet stat kartları --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Tamamlanma')" :value="$completion . '%'" icon="pie-chart" />
        </div>
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Toplam Görev')" :value="$project->tasks->count()" icon="list-checks" />
        </div>
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Harcanan Saat')" :value="number_format($totalHours, 1)" icon="clock" />
        </div>
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Bütçe')" :value="$erpFormat->money($project->budget)" icon="wallet" />
        </div>
    </div>

    <div class="row g-3">
        {{-- Proje Bilgileri --}}
        <div class="col-md-4">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Proje Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Kod') }}</th><td class="font-monospace">{{ $project->code }}</td></tr>
                    <tr><th>{{ __('Müşteri') }}</th><td>{{ $project->customer?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Yönetici') }}</th><td>{{ $project->manager?->full_name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Başlangıç') }}</th><td>{{ $erpFormat->date($project->start_date) }}</td></tr>
                    <tr><th>{{ __('Bitiş') }}</th><td>{{ $erpFormat->date($project->end_date) }}</td></tr>
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ match($project->status) { 'active' => 'success', 'completed' => 'info', 'cancelled' => 'danger', 'on_hold' => 'warning', default => 'secondary' } }}">
                            {{ __($project->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                </table>
            </x-admin-panel::card>

            {{-- Zaman Girişi Ekle --}}
            @can('erp.projects.create')
                <x-admin-panel::card class="mt-3">
                    <h6 class="fw-semibold mb-3">{{ __('Zaman Girişi Ekle') }}</h6>
                    <form method="POST" action="{{ route('erp.projects.time-entries.store', $project) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <x-admin-panel::select name="task_id" :label="__('Görev (opsiyonel)')"
                                    :options="$project->tasks->pluck('name','id')->prepend(__('Görev seçiniz'), '')->toArray()"
                                    :selected="old('task_id')" />
                            </div>
                            <div class="col-12">
                                <x-admin-panel::select name="employee_id" :label="__('Çalışan')" required
                                    :options="\App\Erp\Models\Employee::where('status','active')->orderBy('first_name')->get()->map(fn($e)=>['id'=>$e->id,'name'=>$e->full_name])->pluck('name','id')->prepend(__('Seçiniz'),'')->toArray()"
                                    :selected="old('employee_id')" />
                            </div>
                            <div class="col-6"><x-admin-panel::input name="date" type="date" :label="__('Tarih')" :value="old('date', date('Y-m-d'))" required /></div>
                            <div class="col-6"><x-admin-panel::input name="hours" type="number" step="0.25" :label="__('Saat')" :value="old('hours', '1')" required min="0.25" max="24" /></div>
                            <div class="col-12"><x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description')" /></div>
                        </div>
                        <div class="mt-3">
                            <x-admin-panel::button type="submit" variant="primary" icon="clock">{{ __('Kaydet') }}</x-admin-panel::button>
                        </div>
                    </form>
                </x-admin-panel::card>
            @endcan
        </div>

        {{-- Kanban board --}}
        <div class="col-md-8">
            <x-admin-panel::card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">{{ __('Görevler') }}</h6>
                    @can('erp.projects.create')
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#add-task-form">
                            + {{ __('Görev Ekle') }}
                        </button>
                    @endcan
                </div>

                {{-- Görev ekleme formu --}}
                <div class="collapse mb-3" id="add-task-form">
                    <form method="POST" action="{{ route('erp.projects.tasks.store', $project) }}" class="border rounded p-3 bg-light">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Görev Adı')" :value="old('name')" required /></div>
                            <div class="col-md-4">
                                <x-admin-panel::select name="priority" :label="__('Öncelik')" required
                                    :options="['low'=>__('Düşük'),'medium'=>__('Orta'),'high'=>__('Yüksek'),'urgent'=>__('Acil')]"
                                    :selected="old('priority','medium')" />
                            </div>
                            <div class="col-md-6">
                                <x-admin-panel::select name="assignee_id" :label="__('Atanan')"
                                    :options="\App\Erp\Models\Employee::where('status','active')->orderBy('first_name')->get()->map(fn($e)=>['id'=>$e->id,'name'=>$e->full_name])->pluck('name','id')->prepend(__('Seçiniz'),'')->toArray()"
                                    :selected="old('assignee_id')" />
                            </div>
                            <div class="col-md-3"><x-admin-panel::input name="due_date" type="date" :label="__('Son Tarih')" :value="old('due_date')" /></div>
                            <div class="col-md-3"><x-admin-panel::input name="estimated_hours" type="number" :label="__('Tahmini Saat')" :value="old('estimated_hours','0')" /></div>
                            <input type="hidden" name="status" value="todo">
                        </div>
                        <div class="mt-2">
                            <x-admin-panel::button type="submit" variant="primary" icon="save" size="sm">{{ __('Görev Ekle') }}</x-admin-panel::button>
                        </div>
                    </form>
                </div>

                {{-- Kanban sütunları --}}
                @php
                    $columns = ['todo' => __('Yapılacak'), 'in_progress' => __('Devam Ediyor'), 'review' => __('İncelemede'), 'done' => __('Tamamlandı')];
                    $variants = ['todo' => 'secondary', 'in_progress' => 'info', 'review' => 'warning', 'done' => 'success'];
                @endphp
                <div class="row g-2">
                    @foreach($columns as $statusKey => $statusLabel)
                        <div class="col-md-3">
                            <div class="border rounded p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <x-admin-panel::badge :variant="$variants[$statusKey]">{{ $statusLabel }}</x-admin-panel::badge>
                                    <span class="badge bg-secondary">{{ ($tasksByStatus[$statusKey] ?? collect())->count() }}</span>
                                </div>
                                @forelse(($tasksByStatus[$statusKey] ?? collect()) as $task)
                                    <div class="border rounded p-2 mb-2 bg-white">
                                        <div class="fw-medium small">{{ $task->name }}</div>
                                        @if($task->assignee)
                                            <div class="text-muted" style="font-size:11px">{{ $task->assignee->full_name }}</div>
                                        @endif
                                        @if($task->due_date)
                                            <div class="small {{ $task->isOverdue() ? 'text-danger' : 'text-muted' }}">
                                                {{ $erpFormat->date($task->due_date) }}
                                            </div>
                                        @endif
                                        <div class="d-flex gap-1 mt-1">
                                            @if($statusKey !== 'done')
                                                <form method="POST" action="{{ route('erp.projects.tasks.update-status', [$project, $task]) }}">
                                                    @csrf @method('PATCH')
                                                    @php
                                                        $next = ['todo'=>'in_progress','in_progress'=>'review','review'=>'done'];
                                                        $nextLabel = ['todo'=>'→ '.(__('Başlat')),'in_progress'=>'→ '.(__('İncele')),'review'=>'→ '.(__('Tamam'))];
                                                    @endphp
                                                    <input type="hidden" name="status" value="{{ $next[$statusKey] }}">
                                                    <button type="submit" class="btn btn-xs btn-outline-secondary" style="font-size:10px;padding:1px 5px">{{ $nextLabel[$statusKey] }}</button>
                                                </form>
                                            @endif
                                            @can('erp.projects.update')
                                                <form method="POST" action="{{ route('erp.projects.tasks.destroy', [$project, $task]) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size:10px;padding:1px 5px" onclick="return confirm('?')">×</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted text-center small py-2">{{ __('Görev yok') }}</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin-panel::card>
        </div>

        {{-- Son zaman girişleri --}}
        @if($recentEntries->isNotEmpty())
            <div class="col-12">
                <x-admin-panel::card>
                    <h6 class="fw-semibold mb-3">{{ __('Son Zaman Girişleri') }}</h6>
                    <x-admin-panel::table :headers="[__('Tarih'), __('Çalışan'), __('Görev'), __('Saat'), __('Açıklama'), __('Faturalanabilir')]">
                        @foreach($recentEntries as $entry)
                            <tr>
                                <td>{{ $erpFormat->date($entry->date) }}</td>
                                <td>{{ $entry->employee?->full_name }}</td>
                                <td>{{ $entry->task?->name ?? '-' }}</td>
                                <td>{{ number_format($entry->hours, 2) }}</td>
                                <td>{{ $entry->description ?? '-' }}</td>
                                <td>
                                    <x-admin-panel::badge variant="{{ $entry->billable ? 'success' : 'secondary' }}">
                                        {{ $entry->billable ? __('Evet') : __('Hayır') }}
                                    </x-admin-panel::badge>
                                </td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                </x-admin-panel::card>
            </div>
        @endif
    </div>
@endsection
