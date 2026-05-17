<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('perPage', 15), 50);
        $notifications = $request->user()
            ->notifications()
            ->paginate($perPage);

        $data = $notifications->through(fn($n) => [
            'id' => $n->id,
            'type' => class_basename($n->type),
            'data' => $n->data,
            'readAt' => $n->read_at?->toISOString(),
            'createdAt' => $n->created_at->toISOString(),
        ]);

        return $this->paginated($data);
    }

    /**
     * POST /api/v1/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->success(['message' => 'Notification marked as read.']);
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(['message' => 'All notifications marked as read.']);
    }

    /**
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
