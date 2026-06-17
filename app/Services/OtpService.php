<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(protected TwilioService $twilio) {}

    /**
     * Validate and normalize a phone number to E.164 format.
     * Throws exception if invalid.
     */
    public function validateAndNormalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (empty($phone)) {
            throw new \InvalidArgumentException('Phone number is required.');
        }

        if (strlen($phone) < 10) {
            throw new \InvalidArgumentException('Phone number is too short.');
        }

        if (strlen($phone) > 15) {
            throw new \InvalidArgumentException('Phone number is too long.');
        }

        if (!preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone)) {
            throw new \InvalidArgumentException('Phone number contains invalid characters.');
        }

        // Already in E.164 format
        if (strpos($phone, '+') === 0 && strlen($phone) >= 10 && strlen($phone) <= 15) {
            return $phone;
        }

        // If no + prefix and looks valid, assume it's ready to use
        // (frontend should have already prepended country code)
        if (strpos($phone, '+') !== 0) {
            throw new \InvalidArgumentException('Phone number must include country code (e.g., +966...)');
        }

        return $phone;
    }

    /**
     * Send OTP via email.
     */
    public function sendEmailOtp(string $email): array
    {
        $email = strtolower(trim($email));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        if (OtpCode::isOnCooldown($email)) {
            return [
                'success' => false,
                'message' => 'Please wait 60 seconds before requesting another code.',
            ];
        }

        $otp = OtpCode::generate($email);

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(
                new \App\Mail\OtpMail($otp->code, OtpCode::EXPIRY_MINUTES)
            );

            return [
                'success' => true,
                'channel' => 'email',
                'expiresIn' => OtpCode::EXPIRY_MINUTES * 60,
                'cooldown' => OtpCode::COOLDOWN_SECONDS,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to send email OTP', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to send verification email. Please try again.');
        }
    }

    /**
     * Send OTP via SMS.
     */
    public function sendPhoneOtp(string $phone): array
    {
        $phone = $this->validateAndNormalizePhone($phone);

        if (OtpCode::isOnCooldownByPhone($phone)) {
            return [
                'success' => false,
                'message' => 'Please wait 60 seconds before requesting another code.',
            ];
        }

        $otp = OtpCode::generateForPhone($phone);

        try {
            $this->twilio->sendOtp($phone, $otp->code, OtpCode::EXPIRY_MINUTES);

            return [
                'success' => true,
                'channel' => 'sms',
                'expiresIn' => OtpCode::EXPIRY_MINUTES * 60,
                'cooldown' => OtpCode::COOLDOWN_SECONDS,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to send SMS OTP', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            // Propagate the actual error message instead of a generic one
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Verify email OTP and authenticate user.
     */
    public function verifyEmailOtp(string $email, string $code): array
    {
        $email = strtolower(trim($email));

        $otp = OtpCode::verify($email, $code);
        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ];
        }

        // Find or auto-create user
        $isNewUser = false;
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'language_preference' => app()->getLocale(),
            ]);
            $isNewUser = true;
        } else {
            if (!$user->email_verified_at) {
                $user->update(['email_verified_at' => now()]);
            }
            if ($user->is_banned) {
                return [
                    'success' => false,
                    'message' => 'Your account has been suspended.',
                ];
            }
            if (!$user->is_active) {
                return [
                    'success' => false,
                    'message' => 'Your account is not active.',
                ];
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token,
            'tokenType' => 'Bearer',
            'isNewUser' => $isNewUser,
        ];
    }

    /**
     * Verify phone OTP and authenticate user.
     */
    public function verifyPhoneOtp(string $phone, string $code): array
    {
        $phone = $this->validateAndNormalizePhone($phone);

        $otp = OtpCode::verifyByPhone($phone, $code);
        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ];
        }

        // Find or auto-create user
        $isNewUser = false;
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $user = User::create([
                'name' => 'User ' . substr($phone, -4),
                'email' => null,
                'password' => Hash::make(Str::random(32)),
                'phone' => $phone,
                'phone_verified_at' => now(),
                'language_preference' => app()->getLocale(),
            ]);
            $isNewUser = true;
        } else {
            if (!$user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
            if ($user->is_banned) {
                return [
                    'success' => false,
                    'message' => 'Your account has been suspended.',
                ];
            }
            if (!$user->is_active) {
                return [
                    'success' => false,
                    'message' => 'Your account is not active.',
                ];
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token,
            'tokenType' => 'Bearer',
            'isNewUser' => $isNewUser,
        ];
    }

    /**
     * Verify phone OTP for profile verification.
     */
    public function verifyProfilePhoneOtp(User $user, string $phone, string $code): User
    {
        $phone = $this->validateAndNormalizePhone($phone);

        $otp = OtpCode::verifyByPhone($phone, $code);
        if (!$otp) {
            throw new \InvalidArgumentException('Invalid or expired verification code.');
        }

        $user->update([
            'phone' => $phone,
            'phone_verified_at' => now(),
        ]);

        return $user->fresh();
    }
}
