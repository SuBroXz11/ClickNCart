<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $request->cookie('auth_token'),
        ])->get(env('API_URL') . '/me');

        if ($response->successful()) {
            $user = $response->json();
            if ($user['role'] === 'user' && !$user['email_verified_at']) {
                return redirect()->route('verification.notice');
            }
            return $next($request);
        }

        return redirect()->route('login');
    }
}