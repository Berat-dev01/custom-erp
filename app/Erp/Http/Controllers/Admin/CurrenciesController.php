<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Currency;
use App\Erp\Models\ExchangeRate;
use App\Erp\Services\Currency\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class CurrenciesController extends Controller
{
    public function __construct(private CurrencyService $currencyService) {}

    public function index()
    {
        Gate::authorize('erp.settings.manage');

        $currencies = Currency::orderBy('code')->get();

        $latestRates = ExchangeRate::where('to_currency', config('erp.currency', 'TRY'))
            ->where('rate_date', ExchangeRate::max('rate_date'))
            ->get()
            ->keyBy('from_currency');

        return view('erp::admin.currencies.index', compact('currencies', 'latestRates'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.settings.manage');

        $data = $request->validate([
            'code'   => ['required', 'string', 'size:3', 'uppercase'],
            'name'   => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:5'],
        ]);

        Currency::firstOrCreate(['code' => strtoupper($data['code'])], array_merge($data, [
            'code' => strtoupper($data['code']),
        ]));

        return back()->with('success', __('Para birimi eklendi.'));
    }

    public function storeRate(Request $request)
    {
        Gate::authorize('erp.settings.manage');

        $data = $request->validate([
            'from_currency' => ['required', 'string', 'size:3'],
            'rate'          => ['required', 'numeric', 'min:0.000001'],
            'rate_date'     => ['required', 'date'],
        ]);

        $this->currencyService->saveManualRate(
            strtoupper($data['from_currency']),
            config('erp.currency', 'TRY'),
            (float) $data['rate'],
            \Illuminate\Support\Carbon::parse($data['rate_date'])
        );

        return back()->with('success', __('Kur kaydedildi.'));
    }

    public function fetchTcmb()
    {
        Gate::authorize('erp.settings.manage');

        $saved = $this->currencyService->fetchTcmbRates();

        return back()->with('success', __(':count kur TCMB\'den güncellendi.', ['count' => $saved]));
    }
}
