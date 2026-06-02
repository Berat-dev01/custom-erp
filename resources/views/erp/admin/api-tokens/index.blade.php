@extends('erp::layouts.app')

@section('title', __('API Tokenleri'))
@section('page-title', __('API Tokenleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    @if(session('new_token'))
        <x-admin-panel::alert type="warning" class="mb-4">
            <strong>{{ __('Yeni token oluşturuldu — bir daha gösterilmeyecek:') }}</strong>
            <div class="mt-2 d-flex align-items-center gap-2">
                <code id="new-token-value" class="px-3 py-2 rounded" style="background:rgba(0,0,0,.08);word-break:break-all;font-size:.85rem;">{{ session('new_token') }}</code>
                <button type="button" onclick="navigator.clipboard.writeText('{{ session('new_token') }}').then(()=>this.textContent='✓')" class="btn btn-sm btn-outline-secondary">{{ __('Kopyala') }}</button>
            </div>
        </x-admin-panel::alert>
    @endif

    {{-- Yeni Token Oluştur --}}
    <x-admin-panel::card class="mb-4">
        <h6 class="fw-semibold mb-3">{{ __('Yeni Token Oluştur') }}</h6>
        <form method="POST" action="{{ route('erp.api-tokens.store') }}" class="d-flex gap-2 flex-wrap align-items-end">
            @csrf
            <div>
                <x-admin-panel::input name="name" :label="__('Token Adı')" :value="old('name')" placeholder="{{ __('Örn: Mobile App, Integrasyon') }}" />
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div>
                <x-admin-panel::input name="expires_at" :label="__('Son Kullanma (opsiyonel)')" type="date" :value="old('expires_at')" />
                @error('expires_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="pb-1">
                <x-admin-panel::button type="submit" variant="primary" icon="plus">{{ __('Oluştur') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>

    {{-- Token Listesi --}}
    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3">{{ __('Mevcut Tokenler') }}</h6>
        <x-admin-panel::table :headers="[__('Ad'), __('Son Kullanım'), __('Son Kullanma'), __('Oluşturulma'), '']">
            @forelse($tokens as $token)
                <tr>
                    <td class="fw-medium">{{ $token->name }}</td>
                    <td class="text-muted">{{ $token->last_used_at ? $erpFormat->datetime($token->last_used_at) : __('Hiç kullanılmadı') }}</td>
                    <td>
                        @if($token->expires_at)
                            <x-admin-panel::badge variant="{{ $token->expires_at->isPast() ? 'danger' : 'secondary' }}">
                                {{ $erpFormat->date($token->expires_at) }}
                            </x-admin-panel::badge>
                        @else
                            <span class="text-muted">{{ __('Süresiz') }}</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $erpFormat->datetime($token->created_at) }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('erp.api-tokens.destroy', $token) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                onclick="return confirm('{{ __('Bu tokeni silmek istediğinize emin misiniz?') }}')" />
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Henüz token oluşturulmadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    {{-- API Kullanım Bilgisi --}}
    <x-admin-panel::card class="mt-4">
        <h6 class="fw-semibold mb-3">{{ __('API Kullanımı') }}</h6>
        <p class="text-muted small mb-2">{{ __('Token\'ı Bearer olarak gönder:') }}</p>
        <code class="d-block px-3 py-2 rounded small" style="background:rgba(0,0,0,.06);">
            Authorization: Bearer &lt;token&gt;
        </code>
        <p class="text-muted small mt-3 mb-2">{{ __('Örnek endpoint\'ler:') }}</p>
        <ul class="small text-muted mb-0">
            <li><code>GET {{ url('api/erp/employees') }}</code></li>
            <li><code>GET {{ url('api/erp/products') }}</code></li>
            <li><code>GET {{ url('api/erp/invoices') }}</code></li>
            <li><code>GET {{ url('api/erp/sales-orders') }}</code></li>
            <li><code>POST {{ url('api/erp/sales-orders') }}</code></li>
        </ul>
    </x-admin-panel::card>
@endsection
