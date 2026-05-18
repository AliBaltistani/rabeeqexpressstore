<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Traits\ApiResponse;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'language_preference' => $request->query('lang', 'en'),
        ]);

        // --- Email Notifications ---
        if (setting('email.notify_welcome_email', true)) {
            \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\Customer\WelcomeMail($user));
        }

        $adminEmails = setting('email.admin_email');
        if ($adminEmails && setting('email.admin_notify_new_customer', true)) {
            $admins = array_filter(array_map('trim', explode(',', $adminEmails)));
            if (!empty($admins)) {
                \Illuminate\Support\Facades\Mail::to($admins)->queue(new \App\Mail\Admin\NewCustomerAdminMail($user));
            }
        }
        // ---------------------------

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
            'tokenType' => 'Bearer',
        ], 'Registration successful.', 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->error('The provided credentials are incorrect.', 401);
        }

        if ($user->is_banned) {
            return $this->error('Your account has been suspended. Reason: ' . ($user->ban_reason ?? 'N/A'), 403);
        }

        if (!$user->is_active) {
            return $this->error('Your account is not active.', 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
            'tokenType' => 'Bearer',
        ], 'Login successful.');
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'Password reset link sent to your email.');
        }

        return $this->error('Unable to send reset link. Please check your email address.', 400);
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Password has been reset successfully.');
        }

        return $this->error('Unable to reset password. Invalid or expired token.', 400);
    }

    /**
     * POST /api/v1/auth/send-otp
     * Send a 4-digit OTP to the given email address.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->email));

        // Check rate-limit / cooldown
        if (OtpCode::isOnCooldown($email)) {
            return $this->error('Please wait before requesting another code.', 429);
        }

        // Generate OTP
        $otp = OtpCode::generate($email);

        // Send email
        try {
            Mail::to($email)->send(new OtpMail($otp->code, OtpCode::EXPIRY_MINUTES));
        } catch (\Throwable $e) {
            return $this->error('Failed to send verification email. Please try again.', 500);
        }

        return $this->success([
            'expiresIn' => OtpCode::EXPIRY_MINUTES * 60, // seconds
            'cooldown' => OtpCode::COOLDOWN_SECONDS,
        ], 'Verification code sent to your email.');
    }

    /**
     * POST /api/v1/auth/verify-otp
     * Verify the OTP code and authenticate (or auto-register) the user.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:4'],
        ]);

        $email = strtolower(trim($request->email));

        $otp = OtpCode::verify($email, $request->code);

        if (!$otp) {
            return $this->error('Invalid or expired verification code.', 422);
        }

        // Find or create user
        $isNewUser = false;
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Auto-register: create a passwordless user
            $user = User::create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // random password (user uses OTP)
                'email_verified_at' => now(),
                'language_preference' => $request->query('lang', 'en'),
            ]);
            $isNewUser = true;

            // --- Email Notifications ---
            if (setting('email.notify_welcome_email', true)) {
                \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\Customer\WelcomeMail($user));
            }
            $adminEmails = setting('email.admin_email');
            if ($adminEmails && setting('email.admin_notify_new_customer', true)) {
                $admins = array_filter(array_map('trim', explode(',', $adminEmails)));
                if (!empty($admins)) {
                    \Illuminate\Support\Facades\Mail::to($admins)->queue(new \App\Mail\Admin\NewCustomerAdminMail($user));
                }
            }
            // ---------------------------
        } else {
            // Mark email as verified if not already
            if (!$user->email_verified_at) {
                $user->update(['email_verified_at' => now()]);
            }

            // Check bans/active status
            if ($user->is_banned) {
                return $this->error('Your account has been suspended. Reason: ' . ($user->ban_reason ?? 'N/A'), 403);
            }
            if (!$user->is_active) {
                return $this->error('Your account is not active.', 403);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
            'tokenType' => 'Bearer',
            'isNewUser' => $isNewUser,
        ], $isNewUser ? 'Account created and verified.' : 'Login successful.');
    }

    /**
     * POST /api/v1/auth/resend-otp
     * Resend a new OTP to the given email (same as sendOtp with resend context).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
    }

    /**
     * POST /api/v1/auth/check-email
     * Check whether an email address belongs to a registered account.
     * Returns { exists: bool } — used by checkout Step 1 to branch flows.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $exists = User::where('email', strtolower(trim($request->email)))->exists();

        return $this->success(['exists' => $exists]);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }
}
