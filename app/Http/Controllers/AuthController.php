<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Services\NotifyService;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterUserRequest $request, NotifyService $service): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $service->sendEmailVerificationNotification($user);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    /**
     * Login user and create token.
     * @throws AuthenticationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    /**
     * Logout user (revoke the token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get current auth user details.
     */
    public function authUser(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function verifyEmailByLink(Request $request): JsonResponse
    {
        $id = $request->query('id');
        $hash = $request->query('hash');
        $user = User::findOrFail($id);
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['message' => 'Email verified successfully'], 200);
    }

}
