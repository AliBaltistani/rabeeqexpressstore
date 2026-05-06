<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/profile
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * PUT /api/v1/profile
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'languagePreference' => ['nullable', 'string', 'in:en,ar'],
        ]);

        $user = $request->user();

        if (isset($validated['name'])) $user->name = $validated['name'];
        if (array_key_exists('phone', $validated)) $user->phone = $validated['phone'];
        if (isset($validated['languagePreference'])) $user->language_preference = $validated['languagePreference'];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return $this->success(new UserResource($user), 'Profile updated.');
    }

    /**
     * PUT /api/v1/profile/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currentPassword' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['currentPassword'], $user->password)) {
            return $this->error('Current password is incorrect.', 422, ['currentPassword' => ['Current password is incorrect.']]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return $this->success(null, 'Password updated successfully.');
    }

    // ── Addresses ──

    /**
     * GET /api/v1/addresses
     */
    public function addresses(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'label' => $a->label,
                'firstName' => $a->first_name,
                'lastName' => $a->last_name,
                'phone' => $a->phone,
                'addressLine1' => $a->address_line_1,
                'addressLine2' => $a->address_line_2,
                'city' => $a->city,
                'state' => $a->state,
                'country' => $a->country,
                'postalCode' => $a->postal_code,
                'isDefault' => (bool) $a->is_default,
            ]);

        return $this->success($addresses);
    }

    /**
     * POST /api/v1/addresses
     */
    public function addAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'addressLine1' => ['required', 'string'],
            'addressLine2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['required', 'string'],
            'postalCode' => ['nullable', 'string'],
            'isDefault' => ['nullable', 'boolean'],
        ]);

        // If setting as default, unset all others
        if (!empty($validated['isDefault'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create([
            'label' => $validated['label'] ?? null,
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'phone' => $validated['phone'],
            'address_line_1' => $validated['addressLine1'],
            'address_line_2' => $validated['addressLine2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'],
            'postal_code' => $validated['postalCode'] ?? null,
            'is_default' => $validated['isDefault'] ?? false,
        ]);

        return $this->success(['id' => $address->id], 'Address added.', 201);
    }

    /**
     * PUT /api/v1/addresses/{id}
     */
    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->find($id);
        if (!$address) return $this->notFound('Address not found.');

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'addressLine1' => ['required', 'string'],
            'addressLine2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['required', 'string'],
            'postalCode' => ['nullable', 'string'],
            'isDefault' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['isDefault'])) {
            $request->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update([
            'label' => $validated['label'] ?? $address->label,
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'phone' => $validated['phone'],
            'address_line_1' => $validated['addressLine1'],
            'address_line_2' => $validated['addressLine2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'],
            'postal_code' => $validated['postalCode'] ?? null,
            'is_default' => $validated['isDefault'] ?? $address->is_default,
        ]);

        return $this->success(null, 'Address updated.');
    }

    /**
     * DELETE /api/v1/addresses/{id}
     */
    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->find($id);
        if (!$address) return $this->notFound('Address not found.');

        $address->delete();

        return $this->success(null, 'Address deleted.');
    }
}
