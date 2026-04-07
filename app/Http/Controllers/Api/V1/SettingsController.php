<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * GET /api/v1/billing/checkout-url — returns the web /settings/billing URL.
     * The mobile app opens this in a WebView; user logs in on web (one-time)
     * to complete Stripe Checkout. Subscription state syncs back via webhook.
     */
    public function billingCheckoutUrl(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'url'      => url('/settings/billing'),
            'isPro'    => $user->isPro(),
            'plan'     => $user->plan,
        ]);
    }

    /**
     * GET /api/v1/announcement — current active announcement, if any
     */
    public function announcement(): JsonResponse
    {
        try {
            $raw = Setting::get('announcement');
        } catch (\Throwable) {
            return response()->json(['data' => null]);
        }

        if (! $raw) {
            return response()->json(['data' => null]);
        }

        $data = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($data) || empty($data['is_active'])) {
            return response()->json(['data' => null]);
        }

        if (! empty($data['expires_at']) && now()->isAfter($data['expires_at'])) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'message'   => $data['message']    ?? '',
                'type'      => $data['type']       ?? 'info',
                'updatedAt' => $data['updated_at'] ?? null,
            ],
        ]);
    }

    /**
     * GET /api/v1/notifications — recent notifications for current user
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => class_basename($n->type),
                'data'       => $n->data,
                'read'       => $n->read_at !== null,
                'createdAt'  => $n->created_at->toIso8601String(),
                'timeAgo'    => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'data'        => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /api/v1/notifications/mark-all-read
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read.']);
    }

    /**
     * DELETE /api/v1/notifications/{id}
     */
    public function deleteNotification(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
