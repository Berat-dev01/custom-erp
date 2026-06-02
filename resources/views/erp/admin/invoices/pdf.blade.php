<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        h1 { font-size: 22px; margin: 0 0 4px 0; }
        .label { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f5f5f5; padding: 8px; text-align: left; border-bottom: 2px solid #ddd; font-size: 11px; }
        td { padding: 7px 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .totals-table { width: 300px; float: right; margin-top: 16px; }
        .totals-table td { padding: 4px 8px; }
        .grand-total { font-size: 14px; font-weight: bold; border-top: 2px solid #333; }
        .status-badge { display:inline-block; padding:3px 10px; border-radius:4px; font-size:11px; }
        .header-grid { display: table; width: 100%; }
        .header-left { display: table-cell; width: 50%; vertical-align: top; }
        .header-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
        .divider { border: none; border-top: 1px solid #ddd; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="header-grid">
        <div class="header-left">
            <h1>{{ config('erp.company_name', 'Company') }}</h1>
        </div>
        <div class="header-right">
            <h2 style="font-size:18px;margin:0;">{{ __('FATURA') }}</h2>
            <p style="margin:4px 0 0;font-size:14px;font-weight:bold;">{{ $invoice->invoice_number }}</p>
        </div>
    </div>

    <hr class="divider">

    <div class="header-grid">
        <div class="header-left">
            @if($invoice->invoiceable)
                <p class="label">{{ __('Müşteri / Tedarikçi') }}</p>
                <strong>{{ $invoice->invoiceable->name ?? '-' }}</strong>
            @endif
        </div>
        <div class="header-right">
            <p class="label">{{ __('Düzenleme Tarihi') }}: <strong>{{ $invoice->issue_date->format('d.m.Y') }}</strong></p>
            <p class="label">{{ __('Vade Tarihi') }}: <strong>{{ $invoice->due_date->format('d.m.Y') }}</strong></p>
            @if($invoice->reference)
                <p class="label">{{ __('Referans') }}: <strong>{{ $invoice->reference }}</strong></p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Açıklama') }}</th>
                <th class="text-right">{{ __('Miktar') }}</th>
                <th class="text-right">{{ __('Birim Fiyat') }}</th>
                <th class="text-right">{{ __('KDV %') }}</th>
                <th class="text-right">{{ __('Toplam') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">%{{ $item->tax_rate }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>{{ __('Ara Toplam') }}</td>
            <td class="text-right">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
            <tr>
                <td>{{ __('İndirim') }}</td>
                <td class="text-right">-{{ number_format($invoice->discount_amount, 2) }} {{ $invoice->currency }}</td>
            </tr>
        @endif
        <tr>
            <td>{{ __('KDV') }}</td>
            <td class="text-right">{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}</td>
        </tr>
        <tr class="grand-total">
            <td>{{ __('TOPLAM') }}</td>
            <td class="text-right">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    @if($invoice->notes)
        <div style="clear:both; margin-top:32px;">
            <p class="label">{{ __('Notlar') }}</p>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif
</body>
</html>
