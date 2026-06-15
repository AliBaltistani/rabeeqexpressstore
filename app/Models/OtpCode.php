<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'channel',
        'code',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /** OTP validity duration in minutes. */
    public const EXPIRY_MINUTES = 5;

    /** Minimum seconds between OTP requests. */
    public const COOLDOWN_SECONDS = 60;

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    // ─── Email OTP ──────────────────────────────────────────────────────────

    /**
     * Generate a new email OTP. Invalidates previous unverified codes.
     */
    public static function generate(string $email): static
    {
        static::where('email', strtolower(trim($email)))
            ->where('channel', 'email')
            ->whereNull('verified_at')
            ->delete();

        return static::create([
            'email'      => strtolower(trim($email)),
            'channel'    => 'email',
            'code'       => static::randomCode(),
            'expires_at' => Carbon::now()->addMinutes(static::EXPIRY_MINUTES),
        ]);
    }

    /**
     * Verify an OTP code for the given email.
     */
    public static function verify(string $email, string $code): ?static
    {
        $otp = static::where('email', strtolower(trim($email)))
            ->where('channel', 'email')
            ->where('code', $code)
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) return null;

        $otp->update(['verified_at' => Carbon::now()]);
        return $otp;
    }

    /** Check email OTP cooldown. */
    public static function isOnCooldown(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))
            ->where('created_at', '>', Carbon::now()->subSeconds(static::COOLDOWN_SECONDS))
            ->exists();
    }

    // ─── Phone / SMS OTP ────────────────────────────────────────────────────

    /**
     * Generate a new SMS OTP for a phone number. Invalidates previous codes.
     */
    public static function generateForPhone(string $phone): static
    {
        static::where('phone', $phone)
            ->where('channel', 'sms')
            ->whereNull('verified_at')
            ->delete();

        return static::create([
            'phone'      => $phone,
            'channel'    => 'sms',
            'code'       => static::randomCode(),
            'expires_at' => Carbon::now()->addMinutes(static::EXPIRY_MINUTES),
        ]);
    }

    /**
     * Verify an SMS OTP code for the given phone number.
     */
    public static function verifyByPhone(string $phone, string $code): ?static
    {
        $otp = static::where('phone', $phone)
            ->where('channel', 'sms')
            ->where('code', $code)
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) return null;

        $otp->update(['verified_at' => Carbon::now()]);
        return $otp;
    }

    /** Check SMS OTP cooldown for a phone number. */
    public static function isOnCooldownByPhone(string $phone): bool
    {
        return static::where('phone', $phone)
            ->where('channel', 'sms')
            ->where('created_at', '>', Carbon::now()->subSeconds(static::COOLDOWN_SECONDS))
            ->exists();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private static function randomCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
