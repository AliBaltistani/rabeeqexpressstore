<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class TwilioSettingSeeder extends Seeder
{
    /**
     * Seed default Twilio / SMS auth settings.
     * These act as fallback defaults — overridden by the admin panel.
     * Credentials are intentionally blank; fill them via Settings → SMS & Auth.
     */
    public function run(): void
    {
        $defaults = [
            // Auth / OTP mode
            'auth.otp_mode'          => 'email',   // email | phone | both
            'auth.email_otp_enabled' => true,
            'auth.phone_otp_enabled' => false,

            // Twilio credentials (left blank — must be set in admin panel or .env)
            'twilio.account_sid'          => '',
            'twilio.auth_token'           => '',
            'twilio.from_number'          => '',
            'twilio.messaging_service_sid'=> '',

            // SMS notification toggles (all disabled by default)
            'sms.notify_order_status'  => false,
            'sms.notify_order_shipped' => false,
            'sms.notify_welcome'       => false,
        ];

        foreach ($defaults as $key => $value) {
            // Only seed if the key doesn't already exist — do not overwrite admin settings
            if (\App\Models\Setting::where('key', $key)->doesntExist()) {
                Setting::set($key, $value);
            }
        }
    }
}
