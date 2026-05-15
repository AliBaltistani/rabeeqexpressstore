<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'subtitle' => $this->getTranslation('subtitle', $locale),
            'image' => self::resolveImageUrl($this->image),
            'linkUrl' => $this->link_url,
            'position' => $this->position,
            'sortOrder' => $this->sort_order,
        ];
    }

    /**
     * Resolve image path to full URL.
     * Checks public disk first, then falls back to local disk with signed URL.
     */
    public static function resolveImageUrl(?string $path): string
    {
        $placeholder = asset('storage/dummy/placeholder.jpg');
        if (empty($path)) return $placeholder;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        if (\Illuminate\Support\Facades\Storage::exists($path)) {
            try {
                return \Illuminate\Support\Facades\Storage::temporaryUrl($path, now()->addDay());
            } catch (\Throwable) {
                // If temporaryUrl is not supported, try serving directly
                return asset('storage/' . $path);
            }
        }

        return $placeholder;
    }
}
