<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\ErpApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ApiTokensController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('erp.api.manage');

        $tokens = ErpApiToken::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('erp::admin.api-tokens.index', compact('tokens'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('erp.api.manage');

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $plainText = Str::random(64);

        ErpApiToken::create([
            'user_id'    => $request->user()->id,
            'name'       => $data['name'],
            'token'      => hash('sha256', $plainText),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('new_token', $plainText)
                     ->with('erp_status', __('API tokeni oluşturuldu. Bir daha gösterilmeyecek.'));
    }

    public function destroy(Request $request, ErpApiToken $apiToken): RedirectResponse
    {
        Gate::authorize('erp.api.manage');

        abort_if($apiToken->user_id !== $request->user()->id, 403);

        $apiToken->delete();

        return back()->with('erp_status', __('Token silindi.'));
    }
}
