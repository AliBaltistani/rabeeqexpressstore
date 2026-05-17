<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * Get a list of all active countries for use in frontend selectors (shipping, OTP).
     */
    public function active(): JsonResponse
    {
        $countries = Country::where('is_active', true)
            ->get(['id', 'name_en', 'name_ar', 'code', 'phone_code', 'flag'])
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name, // Mapped dynamically
                    'code' => $country->code,
                    'phone_code' => $country->phone_code,
                    'flag_url' => $country->flag_url,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $countries
        ]);
    }
}
