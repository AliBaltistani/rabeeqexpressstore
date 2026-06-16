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
            $sid   = Setting::get('twilio.account_sid') ?: config('services.twilio.sid');
            $token = Setting::get('twilio.auth_token')  ?: config('services.twilio.token');

            if (!$sid || !$token) {
                throw new \RuntimeException('Twilio credentials are not configured.');
            }

            // By default, Twilio uses its own CurlClient. On local WAMP environments, 
            // we override it to disable strict SSL verification.
            $options = app()->environment('local') ? [CURLOPT_SSL_VERIFYPEER => false] : [];
            $httpClient = new \Twilio\Http\CurlClient($options);

            $this->client = new Client($sid, $token, null, null, $httpClient);
        }

        return $this->client;
    }

    protected function fromNumber(): string
    {
        $from = Setting::get('twilio.from_number') ?: config('services.twilio.from');

        if (!$from) {
            throw new \RuntimeException('Twilio sender number is not configured.');
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
     */
    public function sendSms(string $toPhone, string $body): void
    {
        $params = ['body' => $body];

        $messagingServiceSid = $this->messagingServiceSid();

        if ($messagingServiceSid) {
            $params['messagingServiceSid'] = $messagingServiceSid;
        } else {
            $params['from'] = $this->fromNumber();
        }

        $this->client()->messages->create($toPhone, $params);
    }
}
