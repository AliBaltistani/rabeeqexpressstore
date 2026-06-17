<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Traits\ApiResponse;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TwilioService;
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

    public function __construct(
        protected TwilioService $twilio,
        protected OtpService $otpService
    ) {}

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Resolve the active OTP mode from settings: 'email' | 'phone' | 'both' */
    private function otpMode(): string
    {
        return setting('auth.otp_mode', 'email');
    }

    // ─── Registration ────────────────────────────────────────────────────────

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone'    => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name'                => $validated['name'],
            'email'               => $validated['email'],
            'password'            => Hash::make($validated['password']),
            'phone'               => $validated['phone'] ?? null,
            'language_preference' => $request->query('lang', 'en'),
        ]);

        $this->sendWelcomeNotifications($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user'      => new UserResource($user),
            'token'     => $token,
            'tokenType' => 'Bearer',
        ], 'Registration successful.', 201);
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
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
            'user'      => new UserResource($user),
            'token'     => $token,
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

    // ─── Password Reset ───────────────────────────────────────────────────────

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
            'token'    => ['required'],
            'email'    => ['required', 'email'],
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

    // ─── OTP (Passwordless — Email or Phone) ────────────────────────────────

    /**
     * POST /api/v1/auth/send-otp
     *
     * Accepts either { email } for email OTP or { phone } for SMS OTP.
     * The admin-configured otp_mode controls which channels are allowed.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $mode = $this->otpMode();

        // Determine channel from request
        $channel = $this->resolveChannel($request, $mode);
        if ($channel instanceof JsonResponse) return $channel;

        try {
            if ($channel === 'sms') {
                $request->validate(['phone' => ['required', 'string', 'max:20']]);
                $result = $this->otpService->sendPhoneOtp($request->phone);
            } else {
                $request->validate(['email' => ['required', 'email', 'max:255']]);
                $result = $this->otpService->sendEmailOtp($request->email);
            }

            if (!$result['success']) {
                return $this->error($result['message'], 429);
            }

            return $this->success($result, 'Verification code sent.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/auth/verify-otp
     *
     * Accepts { email, code } for email OTP or { phone, code } for SMS OTP.
     * Auto-creates user account if not found (mirrors email OTP UX).
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $mode    = $this->otpMode();
        $channel = $this->resolveChannel($request, $mode);
        if ($channel instanceof JsonResponse) return $channel;

        $request->validate(['code' => ['required', 'string', 'size:4']]);

        try {
            if ($channel === 'sms') {
                $request->validate(['phone' => ['required', 'string', 'max:20']]);
                $result = $this->otpService->verifyPhoneOtp($request->phone, $request->code);
            } else {
                $request->validate(['email' => ['required', 'email', 'max:255']]);
                $result = $this->otpService->verifyEmailOtp($request->email, $request->code);
            }

            if (!$result['success']) {
                return $this->error($result['message'], 422);
            }

            $this->migrateGuestCart($request, $result['user']);

            return $this->success([
                'user'      => new UserResource($result['user']),
                'token'     => $result['token'],
                'tokenType' => $result['tokenType'],
                'isNewUser' => $result['isNewUser'],
            ], $result['isNewUser'] ? 'Account created and verified.' : 'Login successful.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/auth/resend-otp
     */
    public function resendOtp(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Determine the OTP channel (email|sms) based on request and mode.
     * Returns the channel string or a JsonResponse error if channel is not allowed.
     */
    private function resolveChannel(Request $request, string $mode): string|JsonResponse
    {
        $hasPhone = $request->filled('phone');
        $hasEmail = $request->filled('email');

        if ($mode === 'email') {
            return 'email';
        }

        if ($mode === 'phone') {
            return 'sms';
        }

        // 'both' mode: determine from what the user submitted
        if ($hasPhone) return 'sms';
        if ($hasEmail) return 'email';

        return $this->error('Please provide an email or phone number.', 422);
    }

    /**
     * Migrate guest cart items to an authenticated user.
     */
    private function migrateGuestCart(Request $request, User $user): void
    {
        $sessionId = $request->session()->getId();
        if (!$sessionId) return;

        $guestItems = \App\Models\CartItem::where('session_id', $sessionId)->get();

        foreach ($guestItems as $guestItem) {
            $existing = \App\Models\CartItem::where('user_id', $user->id)
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->where('selected_attribute_values', $guestItem->selected_attribute_values)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $guestItem->quantity]);
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $user->id, 'session_id' => null]);
            }
        }
    }

    /**
     * Send welcome email + admin new-customer notification.
     */
    private function sendWelcomeNotifications(User $user): void
    {
        if ($user->email && setting('email.notify_welcome_email', true)) {
            Mail::to($user->email)->queue(new \App\Mail\Customer\WelcomeMail($user));
        }

        $adminEmails = setting('email.admin_email');
        if ($adminEmails && setting('email.admin_notify_new_customer', true)) {
            $admins = array_filter(array_map('trim', explode(',', $adminEmails)));
            if (!empty($admins)) {
                Mail::to($admins)->queue(new \App\Mail\Admin\NewCustomerAdminMail($user));
            }
        }
    }
}
