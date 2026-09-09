<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\IssuesArenaAuthToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use IssuesArenaAuthToken;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'user',
            'status' => 'active',
        ]);

        try {
            $user->assignRole('user');
        } catch (\Throwable $e) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $user->assignRole('user');
        }

        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user));
        } catch (\Throwable $e) {
            // Mail may be log driver in local dev
        }

        $user->syncRolesWithColumn();

        return $this->authJsonResponse($user, 'Registration successful. Please check your email to verify your account.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status === 'banned') {
            throw ValidationException::withMessages([
                'email' => ['This account has been banned.'],
            ]);
        }

        if (config('arena.require_email_verification') && ! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Please verify your email before logging in.'],
            ]);
        }

        $user->syncRolesWithColumn();

        return $this->authJsonResponse($user, 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->clearAuthCookie(response()->json([
            'message' => 'Logged out successfully.',
        ]));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount(['registrations', 'orders']);
        $user->syncRolesWithColumn();

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'email_verified' => (bool) $user->email_verified_at,
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link.'], 403);
        }

        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully. You can now log in.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.']);
        }

        Mail::to($user->email)->send(new VerifyEmailMail($user));

        return response()->json(['message' => 'Verification email sent.']);
    }
}
