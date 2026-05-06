<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'customerName' => $this->user?->name ?? 'Anonymous',
            'adminReply' => $this->admin_reply,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
