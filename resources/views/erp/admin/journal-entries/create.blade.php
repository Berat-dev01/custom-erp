@extends('erp::layouts.app')

@section('title', __('Manuel Yevmiye Fişi'))
@section('page-title', __('Manuel Yevmiye Fişi'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.journal-entries.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Fişlere Dön') }}
        </x-admin-panel::button>
    </div>

    @if($errors->any())
        <x-admin-panel::alert type="error" dismissible class="mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </x-admin-panel::alert>
    @endif

    <form method="POST" action="{{ route('erp.journal-entries.store') }}">
        @csrf
        <x-admin-panel::card class="mb-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description')" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="entry_date" :label="__('Fiş Tarihi')" type="date" :value="old('entry_date', today()->format('Y-m-d'))" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="reference" :label="__('Referans (opsiyonel)')" :value="old('reference')" />
                </div>
            </div>
        </x-admin-panel::card>

        <x-admin-panel::card class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">{{ __('Fiş Kalemleri') }}</h6>
                <x-admin-panel::button type="button" id="add-line" variant="outline" icon="plus" size="sm">{{ __('Satır Ekle') }}</x-admin-panel::button>
            </div>

            <div id="lines-container">
                @php $oldLines = old('lines', [['account_id'=>'','debit'=>'0','credit'=>'0','description'=>''],['account_id'=>'','debit'=>'0','credit'=>'0','description'=>'']]) @endphp
                @foreach($oldLines as $i => $line)
                    <div class="line-row row g-2 mb-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('Hesap') }}</label>
                            <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" required>
                                <option value="">{{ __('Hesap Seçin') }}</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ $line['account_id'] == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} — {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Açıklama') }}</label>
                            <input type="text" name="lines[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $line['description'] ?? '' }}" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('Borç') }}</label>
                            <input type="number" step="0.01" name="lines[{{ $i }}][debit]" class="form-control form-control-sm debit-input" value="{{ $line['debit'] ?? '0' }}" min="0" required />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('Alacak') }}</label>
                            <input type="number" step="0.01" name="lines[{{ $i }}][credit]" class="form-control form-control-sm credit-input" value="{{ $line['credit'] ?? '0' }}" min="0" required />
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-line w-100">×</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 d-flex gap-4 justify-content-end">
                <div><span class="text-muted small">{{ __('Toplam Borç:') }}</span> <span id="total-debit" class="fw-bold">0,00</span></div>
                <div><span class="text-muted small">{{ __('Toplam Alacak:') }}</span> <span id="total-credit" class="fw-bold">0,00</span></div>
                <div><span class="text-muted small">{{ __('Fark:') }}</span> <span id="total-diff" class="fw-bold">0,00</span></div>
            </div>
        </x-admin-panel::card>

        <div class="d-flex justify-content-end gap-2">
            <x-admin-panel::button href="{{ route('erp.journal-entries.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
            <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function() {
    let lineIndex = {{ count($oldLines) }};
    const accountOptions = `@foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} — {{ addslashes($acc->name) }}</option>@endforeach`;

    function updateTotals() {
        let debit = 0, credit = 0;
        document.querySelectorAll('.debit-input').forEach(i => debit += parseFloat(i.value)||0);
        document.querySelectorAll('.credit-input').forEach(i => credit += parseFloat(i.value)||0);
        const fmt = v => new Intl.NumberFormat('tr-TR',{minimumFractionDigits:2}).format(v);
        document.getElementById('total-debit').textContent = fmt(debit);
        document.getElementById('total-credit').textContent = fmt(credit);
        const diff = debit - credit;
        const el = document.getElementById('total-diff');
        el.textContent = fmt(Math.abs(diff));
        el.className = 'fw-bold ' + (Math.abs(diff) < 0.01 ? 'text-success' : 'text-danger');
    }

    document.getElementById('add-line').addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'line-row row g-2 mb-2 align-items-end';
        div.innerHTML = `
            <div class="col-md-4"><label class="form-label small">{{ __('Hesap') }}</label>
            <select name="lines[${lineIndex}][account_id]" class="form-select form-select-sm" required><option value="">{{ __('Hesap Seçin') }}</option>${accountOptions}</select></div>
            <div class="col-md-3"><label class="form-label small">{{ __('Açıklama') }}</label>
            <input type="text" name="lines[${lineIndex}][description]" class="form-control form-control-sm" /></div>
            <div class="col-md-2"><label class="form-label small">{{ __('Borç') }}</label>
            <input type="number" step="0.01" name="lines[${lineIndex}][debit]" class="form-control form-control-sm debit-input" value="0" min="0" required /></div>
            <div class="col-md-2"><label class="form-label small">{{ __('Alacak') }}</label>
            <input type="number" step="0.01" name="lines[${lineIndex}][credit]" class="form-control form-control-sm credit-input" value="0" min="0" required /></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-line w-100">×</button></div>`;
        document.getElementById('lines-container').appendChild(div);
        div.querySelectorAll('input').forEach(i => i.addEventListener('input', updateTotals));
        div.querySelector('.remove-line').addEventListener('click', () => { div.remove(); updateTotals(); });
        lineIndex++;
        updateTotals();
    });

    document.querySelectorAll('.debit-input, .credit-input').forEach(i => i.addEventListener('input', updateTotals));
    document.querySelectorAll('.remove-line').forEach(btn => btn.addEventListener('click', () => { btn.closest('.line-row').remove(); updateTotals(); }));

    updateTotals();
})();
</script>
@endpush
