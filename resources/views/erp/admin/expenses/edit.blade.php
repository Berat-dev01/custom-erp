@extends('erp::layouts.app')

@section('title', __('Gider Düzenle'))
@section('page-title', __('Gider Düzenle'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.expenses.update', $expense) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-8"><x-admin-panel::input name="title" :label="__('Başlık')" :value="old('title', $expense->title)" required /></div>
                <div class="col-md-4">
                    <x-admin-panel::select name="category" :label="__('Kategori')" required
                        :options="['office' => __('Ofis'), 'travel' => __('Seyahat'), 'utilities' => __('Faturalar'), 'salary' => __('Maaş'), 'rent' => __('Kira'), 'marketing' => __('Pazarlama'), 'other' => __('Diğer')]"
                        :selected="old('category', $expense->category)" />
                </div>
                <div class="col-md-4"><x-admin-panel::input name="amount" type="number" step="0.01" :label="__('Tutar')" :value="old('amount', $expense->amount)" required /></div>
                <div class="col-md-4"><x-admin-panel::input name="expense_date" type="date" :label="__('Tarih')" :value="old('expense_date', $expense->expense_date?->format('Y-m-d'))" required /></div>
                <div class="col-md-4">
                    <x-admin-panel::select name="payment_method" :label="__('Ödeme Yöntemi')" required
                        :options="['cash' => __('Nakit'), 'bank_transfer' => __('Banka Transferi'), 'credit_card' => __('Kredi Kartı'), 'other' => __('Diğer')]"
                        :selected="old('payment_method', $expense->payment_method)" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Yeni Fiş / Belge') }}</label>
                    <input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    @if($expense->receipt_path)
                        <small class="text-muted">{{ __('Mevcut dosya var') }}</small>
                    @endif
                </div>
                <div class="col-12"><x-admin-panel::textarea name="notes" :label="__('Notlar')" rows="2">{{ old('notes', $expense->notes) }}</x-admin-panel::textarea></div>
            </div>

            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Güncelle') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.expenses.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
