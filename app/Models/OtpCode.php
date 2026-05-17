<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * OTP validity duration in minutes.
     */
    public const EXPIRY_MINUTES = 5;

    /**
     * Minimum seconds between OTP requests for the same email.
     */
    public const COOLDOWN_SECONDS = 60;

    /**
     * Check if this OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if this OTP has already been verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Generate a new OTP for the given email.
     * Invalidates any previous unverified codes for this email.
     *
     * @return static The newly created OTP record.
     */
    public static function generate(string $email): static
    {
        // Invalidate all previous unverified OTPs for this email
        static::where('email', $email)
            ->whereNull('verified_at')
            ->delete();

        return static::create([
            'email' => strtolower(trim($email)),
            'code' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'expires_at' => Carbon::now()->addMinutes(static::EXPIRY_MINUTES),
        ]);
    }

    /**
     * Verify an OTP code for the given email.
     *
     * @return static|null The verified OTP record, or null if invalid.
     */
    public static function verify(string $email, string $code): ?static
    {
        $otp = static::where('email', strtolower(trim($email)))
            ->where('code', $code)
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otp) {
            return null;
        }

        $otp->update(['verified_at' => Carbon::now()]);

        return $otp;
    }

    /**
     * Check if an OTP was recently sent to this email (within cooldown period).
     */
    public static function isOnCooldown(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))
            ->where('created_at', '>', Carbon::now()->subSeconds(static::COOLDOWN_SECONDS))
            ->exists();
    }
}
