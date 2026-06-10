@if(session('erp_status'))
    <x-admin-panel::alert variant="success" class="erp-toast" data-erp-toast>
        {{ session('erp_status') }}
    </x-admin-panel::alert>
@endif

@php($erpErrors = $errors->any() ? $errors : session('errors'))

@if($erpErrors && $erpErrors->any())
    <x-admin-panel::alert variant="danger">
        <strong>{{ __('Lütfen işaretli alanları kontrol edin.') }}</strong>
        <span>{{ $erpErrors->first() }}</span>
    </x-admin-panel::alert>
@endif
