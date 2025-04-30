<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->isBlocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been blocked'
                ], 403);
            }

            if ($user->isRetailer() && $user->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your retailer account is pending approval'
                ], 403);
            }

            // if ($user->isUser() && !$user->hasVerifiedEmail()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Please verify your email first'
            //     ], 403);
            // }

            // if (!empty($roles) && !in_array($user->role, $roles)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthorized access'
            //     ], 403);
            // }

        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is invalid'
                ], 401);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired'
                ], 401);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token not found'
                ], 401);
            }
        }

        return $next($request);
    }
}