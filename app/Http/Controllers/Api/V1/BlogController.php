<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BlogPostResource;
use App\Http\Traits\ApiResponse;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/blog/posts
     */
    public function posts(Request $request): JsonResponse
    {
        $query = BlogPost::where('status', 'published')
            ->with(['category', 'admin'])
            ->orderByDesc('published_at');

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $category));
        }

        $posts = $query->paginate(12);

        return $this->paginated($posts, BlogPostResource);
    }

    /**
     * GET /api/v1/blog/posts/{slug}
     */
    public function showPost(string $slug): JsonResponse
    {
        $post = BlogPost::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'admin'])
            ->first();

        if (!$post) return $this->notFound('Blog post not found.');

        // Increment view count
        $post->increment('view_count');

        return $this->success(new BlogPostResource($post));
    }

    /**
     * GET /api/v1/blog/categories
     */
    public function categories(): JsonResponse
    {
        $locale = app()->getLocale();

        $categories = BlogCategory::withCount(['posts' => fn($q) => $q->where('status', 'published')])
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->getTranslation('name', $locale),
                'slug' => $c->slug ?? null,
                'postCount' => $c->posts_count,
            ]);

        return $this->success($categories);
    }
}
