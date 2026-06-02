<?php

namespace App\Erp\Http\Middleware;

use App\Erp\Models\ErpApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateErpApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveBearerUser($request);

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::shouldUse('web');
        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function resolveBearerUser(Request $request): mixed
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return null;
        }

        $token = ErpApiToken::query()
            ->with('user')
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if (! $token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        if (! $token->last_used_at || $token->last_used_at->diffInSeconds(now()) > 60) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        return $token->user;
    }
}
