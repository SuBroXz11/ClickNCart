<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\VerificationEmail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'role' => 'required|string|in:user,retailer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'role' => $request->role,
            'status' => User::STATUS_PENDING, // All users start as pending
            'email_verification_code' => Str::random(6),
        ];

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile', 'public');
            $userData['profile_picture'] = $path;
        }

        if ($request->role === User::ROLE_RETAILER) {
            $retailerValidator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'tax_id' => 'required|string|max:255',
                'business_registration_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($retailerValidator->fails()) {
                return response()->json($retailerValidator->errors(), 400);
            }

            $userData['business_name'] = $request->business_name;
            $userData['tax_id'] = $request->tax_id;
            
            if ($request->hasFile('business_registration_image')) {
                $path = $request->file('business_registration_image')->store('business_registrations', 'public');
                $userData['business_registration_image'] = $path;
            }
        }

        $user = User::create($userData);

        // Send verification email to both users and retailers
        try {
            Mail::to($user->email)->send(new VerificationEmail($user->email_verification_code));
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
        }

        $token = JWTAuth::fromUser($user);
        return response()->json([
            'message' => 'Registration successful. Please check your email for verification code.',
            'token' => $token,
            'expires_in' => config('jwt.ttl') * 60, 
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        $user = JWTAuth::user();

        if ($user->isBlocked()) {
            return response()->json(['error' => 'Your account has been blocked'], 403);
        }

        // Different verification flows for retailers and regular users
        if ($user->isRetailer()) {
            // For retailers: Check both email verification and admin approval
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['error' => 'Please verify your email first'], 403);
            }
            
            if ($user->isPending()) {
                return response()->json(['error' => 'Your retailer account is pending admin approval. You will be notified once approved.'], 403);
            }
            
            if (!$user->isActive()) {
                return response()->json(['error' => 'Your retailer account is not active'], 403);
            }
        } else {
            // For regular users: Only check email verification
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['error' => 'Please verify your email first'], 403);
            }
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, 
            'user' => $user,
        ]);
    }

    public function refreshToken()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            return response()->json([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);
        Log::error('Request Validated ' );
        $user = JWTAuth::user();
        

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        if ($user->email_verification_code === $request->code) {
            $user->email_verified_at = now();
            $user->email_verification_code = null;
            $user->save();
            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['error' => 'Invalid verification code'], 400);
    }

    public function resendVerification(Request $request)
{
    $user = JWTAuth::user();

    // Check if user is a regular user (not retailer/admin) and hasn't verified email
    if (!$user->isUser() || $user->hasVerifiedEmail()) {
        return response()->json([
            'error' => 'Email verification not required for your account'
        ], 400);
    }

    // Generate new verification code
    $newCode = Str::random(6);
    $user->email_verification_code = $newCode;
    $user->save();

    try {
        Mail::to($user->email)->send(new VerificationEmail($newCode));
        return response()->json([
            'message' => 'New verification code sent to your email'
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to resend verification email: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to send verification email. Please try again later.'
        ], 500);
    }
}

    public function me()
    {
        return response()->json(JWTAuth::user());
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function showLoginForm()
    {
        return response()->file(resource_path('views/auth/login.html'));
    }
    

public function showRegistrationForm()
{
    return response()->file(resource_path('views/auth/register.html'));
}

}   