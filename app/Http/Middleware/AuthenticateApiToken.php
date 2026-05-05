<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return response()->json([
                'message' => 'Authentication token is missing.',
            ], 401);
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if (! $token || ! $token->user || $token->user->status !== 'active') {
            return response()->json([
                'message' => 'Invalid or expired token.',
            ], 401);
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
