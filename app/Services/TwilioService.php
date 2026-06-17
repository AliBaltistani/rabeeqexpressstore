<?php

namespace App\Services;

use App\Models\Setting;
use Twilio\Rest\Client;

class TwilioService
{
    protected ?Client $client = null;

    protected function client(): Client
    {
        if (!$this->client) {
            $sid   = Setting::get('twilio.account_sid');
            $token = Setting::get('twilio.auth_token');

            if (!$sid || !$token) {
                throw new \RuntimeException(
                    'SMS is not configured. Please add your Twilio credentials in Admin → Settings → SMS & Auth.'
                );
            }

            // On local WAMP environments disable strict SSL verification.
            $options = app()->environment('local') ? [CURLOPT_SSL_VERIFYPEER => false] : [];
            $httpClient = new \Twilio\Http\CurlClient($options);

            $this->client = new Client($sid, $token, null, null, $httpClient);
        }

        return $this->client;
    }

    protected function fromNumber(): string
    {
        $from = Setting::get('twilio.from_number');

        if (!$from) {
            throw new \RuntimeException(
                'SMS sender number is not configured. Please set the From Number in Admin → Settings → SMS & Auth.'
            );
        }

        return $from;
    }

    protected function messagingServiceSid(): ?string
    {
        return Setting::get('twilio.messaging_service_sid') ?: null;
    }


    /**
     * Send an OTP code via SMS.
     */
    public function sendOtp(string $toPhone, string $code, int $expiryMinutes = 5): void
    {
        $storeName = Setting::get('general.store_name_en', config('app.name'));
        $body = "Your {$storeName} verification code is: {$code}. Valid for {$expiryMinutes} minutes. Do not share this code.";

        $this->sendSms($toPhone, $body);
    }

    /**
     * Send a raw SMS message to a phone number.
     * Phone must be in E.164 format (e.g., +966501234567)
     */
    public function sendSms(string $toPhone, string $body): void
    {
        // Validate E.164 format
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $toPhone)) {
            throw new \InvalidArgumentException('Phone number must be in E.164 format (e.g., +966501234567). Received: ' . $toPhone);
        }

        $params = ['body' => $body];

        $messagingServiceSid = $this->messagingServiceSid();

        if ($messagingServiceSid) {
            $params['messagingServiceSid'] = $messagingServiceSid;
        } else {
            $params['from'] = $this->fromNumber();
        }

        try {
            $this->client()->messages->create($toPhone, $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Twilio SMS send failed', [
                'phone' => $toPhone,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            
            $rawMsg = $e->getMessage();
            
            // 1. Strip HTTP status and technical prefix
            $msg = preg_replace('/^\[HTTP \d+\] Unable to create record:\s*/i', '', $rawMsg);
            
            // 2. Hide sensitive Account SID
            $msg = preg_replace('/Account AC[a-f0-9]{32}/i', 'This account', $msg);
            
            // 3. User-friendly mapping for common Twilio trial/validation errors
            if (stripos($msg, 'unverified') !== false) {
                $msg = 'Failed to send SMS: The provided phone number is unverified.';
            } elseif (stripos($msg, 'daily messages limit') !== false) {
                $msg = 'Daily SMS message limit has been exceeded. Please try again later.';
            } elseif (stripos($msg, 'not a valid phone number') !== false) {
                $msg = 'The provided phone number is invalid.';
            }
            
            throw new \RuntimeException($msg);
        }
    }
}
