@extends('erp::layouts.app')
@section('title', __('İzin Talepleri'))
@section('content')
    @php
        $activeFilterCount = collect($filters)->except(['sort','direction'])->filter(fn($v) => $v !== null && $v !== '')->count();
        $tableHeaders = [
            ['label' => __('Çalışan')], ['label' => __('İzin Türü')], ['label' => __('Başlangıç')],
            ['label' => __('Bitiş')], ['label' => __('Gün')], ['label' => __('Durum')], ['label' => __('İşlemler'), 'width' => '120px'],
        ];
    @endphp
    <section class="crm-admin-page" data-crm-module="erp-leave-requests">
        @include('erp::admin.partials.status')
        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">ERP</p>
                <h1>{{ __('İzin Talepleri') }} @if($pendingCount > 0)<x-admin-panel::badge variant="warning">{{ $pendingCount }}</x-admin-panel::badge>@endif</h1>
            </div>
            <div class="crm-admin-actions">
                @can('erp.leave.create')<x-admin-panel::button :href="route('erp.leave-requests.create')" icon="plus">{{ __('Yeni Talep') }}</x-admin-panel::button>@endcan
            </div>
        </header>
        <div id="erp-leave-requests-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('erp.leave-requests.index')" :reset-url="route('erp.leave-requests.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::select name="status" label="Durum" :options="['pending'=>__('Bekliyor'),'approved'=>__('Onaylandı'),'rejected'=>__('Reddedildi'),'cancelled'=>__('İptal')]" :selected="$filters['status']" placeholder="Tüm durumlar" />
                    <div class="form-group" data-admin-select data-admin-select-placeholder="{{ __('Tüm çalışanlar') }}" data-admin-select-searchable="1" data-admin-select-clearable="1">
                        <label class="form-label">{{ __('Çalışan') }}</label>
                        <select name="employee_id" class="form-control" data-admin-select-native>
                            <option value=""></option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $filters['employee_id'] == $emp->id ? 'selected' : '' }}>{{ $emp->last_name }} {{ $emp->first_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot:compact>
                <x-slot:advanced>
                    <x-admin-panel::select name="sort" label="Sırala" :selected="$filters['sort']" :options="['created_at'=>__('Eklenme'),'start_date'=>__('Başlangıç')]" />
                </x-slot:advanced>
            </x-admin-panel::filter-shell>
            <x-admin-panel::card>
                <x-slot:header>{{ __('İzin Talepleri') }}</x-slot:header>
                <x-admin-panel::table :headers="$tableHeaders">
                @forelse($requests as $req)
                    <tr>
                        <td>{{ $req->employee?->last_name }} {{ $req->employee?->first_name }}</td>
                        <td>{{ $req->leaveType?->name ?? '-' }}</td>
                        <td>{{ $req->start_date?->format('d.m.Y') }}</td>
                        <td>{{ $req->end_date?->format('d.m.Y') }}</td>
                        <td>{{ $req->days }}</td>
                        <td><x-admin-panel::badge variant="{{ match($req->status) { 'approved'=>'success','pending'=>'warning','rejected'=>'danger',default=>'secondary' } }}">{{ $req->status }}</x-admin-panel::badge></td>
                        <td>
                            <div class="crm-row-actions">
                                @if($req->status === 'pending')
                                    @can('erp.leave.approve')
                                        <form method="POST" action="{{ route('erp.leave-requests.approve', $req) }}" style="display:inline">
                                            @csrf @method('PATCH')
                                            <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="check" />
                                        </form>
                                        <form method="POST" action="{{ route('erp.leave-requests.reject', $req) }}" style="display:inline">
                                            @csrf @method('PATCH')
                                            <x-admin-panel::button type="submit" size="sm" variant="danger" icon="x" data-admin-confirm="{{ __('Bu talebi reddetmek istediğinize emin misiniz?') }}" />
                                        </form>
                                    @endcan
                                    @can('erp.leave.view')
                                        <form method="POST" action="{{ route('erp.leave-requests.cancel', $req) }}" style="display:inline">
                                            @csrf @method('PATCH')
                                            <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="ban" data-admin-confirm="{{ __('Bu talebi iptal etmek istediğinize emin misiniz?') }}" />
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('erp::admin.partials.empty-state',['title'=>__('İzin talebi bulunamadı.')])</td></tr>
                @endforelse
                </x-admin-panel::table>
                <x-admin-panel::pagination :paginator="$requests" class="crm-pagination" />
            </x-admin-panel::card>
        </div>
    </section>
@endsection
