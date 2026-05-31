<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birthDate' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'profileCompleted' => (bool) $this->profile_completed,
            'promotionalMessages' => (bool) ($this->promotional_messages ?? true),
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'walletBalance' => (float) ($this->wallet_balance ?? 0),
            'loyaltyPoints' => (int) ($this->loyalty_points ?? 0),
            'languagePreference' => $this->language_preference ?? 'en',
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
