<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/products/{slug}/reviews
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->first();
        if (!$product) return $this->notFound('Product not found.');

        $query = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->with('user')
            ->orderByDesc('created_at');

        if ($rating = $request->query('rating')) {
            $query->where('rating', (int) $rating);
        }

        $reviews = $query->paginate(10);

        return $this->paginated($reviews, ReviewResource);
    }

    /**
     * POST /api/v1/products/{slug}/reviews
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        if (!setting('general.enable_reviews', true)) {
            return $this->error('Reviews are disabled.', 403);
        }

        $product = Product::where('slug', $slug)->first();
        if (!$product) return $this->notFound('Product not found.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        // Check if user already reviewed this product
        $exists = Review::where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return $this->error('You have already reviewed this product.', 422);
        }

        $requireApproval = (bool) setting('general.reviews_require_approval', true);

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'status' => $requireApproval ? 'pending' : 'approved',
        ]);

        $message = $requireApproval
            ? 'Thank you! Your review has been submitted for approval.'
            : 'Thank you for your review!';

        return $this->success(new ReviewResource($review), $message, 201);
    }
}
