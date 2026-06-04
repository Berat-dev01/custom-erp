<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; padding: 20px; }
        h2  { font-size: 18px; margin: 0 0 4px; }
        .label { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th  { background: #f5f5f5; padding: 8px; text-align: left; font-size: 11px; border-bottom: 2px solid #ddd; }
        td  { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-danger { color: #dc2626; }
        .text-success { color: #16a34a; }
        .grand-total { font-size: 14px; font-weight: bold; background: #f0fdf4; }
        .header-grid { display: table; width: 100%; margin-bottom: 16px; }
        .col-left { display: table-cell; width: 50%; vertical-align: top; }
        .col-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
    </style>
</head>
<body>
    <div class="header-grid">
        <div class="col-left">
            <h2>{{ config('erp.company_name', 'Company') }}</h2>
            <p class="label">{{ __('BORDO') }}</p>
        </div>
        <div class="col-right">
            <p class="label">{{ __('Dönem') }}: <strong>{{ $payslip->payrollRun?->periodLabel() }}</strong></p>
        </div>
    </div>

    <table style="margin-bottom:16px;">
        <tr>
            <th>{{ __('Çalışan') }}</th>
            <th>{{ __('Sicil No') }}</th>
            <th>{{ __('Departman') }}</th>
            <th>{{ __('Pozisyon') }}</th>
        </tr>
        <tr>
            <td>{{ $payslip->employee?->full_name }}</td>
            <td>{{ $payslip->employee?->employee_number }}</td>
            <td>{{ $payslip->employee?->department?->name ?? '-' }}</td>
            <td>{{ $payslip->employee?->position?->name ?? '-' }}</td>
        </tr>
    </table>

    @php $bd = $payslip->breakdown ?? []; @endphp
    <table>
        <tr><th colspan="2">{{ __('Kazançlar') }}</th></tr>
        <tr><td>{{ __('Brüt Maaş') }}</td><td class="text-right">{{ number_format($bd['gross_salary'] ?? $payslip->gross_salary, 2, ',', '.') }} ₺</td></tr>

        <tr><th colspan="2">{{ __('Kesintiler') }}</th></tr>
        @if(isset($bd['sgk_worker']))
            <tr><td>{{ __('SGK İşçi Payı (%14)') }}</td><td class="text-right text-danger">-{{ number_format($bd['sgk_worker'], 2, ',', '.') }} ₺</td></tr>
        @endif
        @if(isset($bd['unemployment_worker']))
            <tr><td>{{ __('İşsizlik İşçi (%1)') }}</td><td class="text-right text-danger">-{{ number_format($bd['unemployment_worker'], 2, ',', '.') }} ₺</td></tr>
        @endif
        @if(isset($bd['income_tax']))
            <tr><td>{{ __('Gelir Vergisi') }}</td><td class="text-right text-danger">-{{ number_format($bd['income_tax'], 2, ',', '.') }} ₺</td></tr>
        @endif
        @if(isset($bd['stamp_tax']))
            <tr><td>{{ __('Damga Vergisi') }}</td><td class="text-right text-danger">-{{ number_format($bd['stamp_tax'], 2, ',', '.') }} ₺</td></tr>
        @endif
        <tr><td><strong>{{ __('Toplam Kesinti') }}</strong></td><td class="text-right text-danger"><strong>-{{ number_format($payslip->total_deductions, 2, ',', '.') }} ₺</strong></td></tr>

        <tr class="grand-total">
            <td>{{ __('NET ÖDEME') }}</td>
            <td class="text-right text-success">{{ number_format($payslip->net_salary, 2, ',', '.') }} ₺</td>
        </tr>
    </table>
</body>
</html>
