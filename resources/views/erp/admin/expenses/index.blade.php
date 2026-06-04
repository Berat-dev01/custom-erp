@extends('erp::layouts.app')

@section('title', __('Giderler'))
@section('page-title', __('Giderler'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.expenses.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="category"
                :options="['' => __('Tüm Kategoriler'), 'office' => __('Ofis'), 'travel' => __('Seyahat'), 'utilities' => __('Faturalar'), 'salary' => __('Maaş'), 'rent' => __('Kira'), 'marketing' => __('Pazarlama'), 'other' => __('Diğer')]"
                :selected="request('category')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::input name="date_to" type="date" :value="request('date_to')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.expenses.create')
            <x-admin-panel::button href="{{ route('erp.expenses.create') }}" icon="plus" variant="primary">{{ __('Yeni Gider') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Başlık'), __('Kategori'), __('Tarih'), __('Ödeme Yöntemi'), __('Tutar'), '']">
            @forelse($expenses as $exp)
                <tr>
                    <td>{{ $exp->title }}</td>
                    <td><x-admin-panel::badge variant="secondary">{{ __($exp->category) }}</x-admin-panel::badge></td>
                    <td>{{ $erpFormat->date($exp->expense_date) }}</td>
                    <td>{{ __($exp->payment_method) }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($exp->amount) }}</td>
                    <td class="text-end">
                        @can('erp.expenses.update')
                            <x-admin-panel::button href="{{ route('erp.expenses.edit', $exp) }}" size="sm" variant="ghost" icon="pencil" />
                        @endcan
                        @can('erp.expenses.delete')
                            <form method="POST" action="{{ route('erp.expenses.destroy', $exp) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                    onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Gider bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $expenses->links() }}</div>
@endsection
