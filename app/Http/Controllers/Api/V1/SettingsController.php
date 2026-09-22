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
                'title'      => self::notificationTitle($n->data ?? []),
                'message'    => self::notificationMessage($n->data ?? []),
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
        if (\Illuminate\Support\Str::isUuid($id)) {
            $request->user()->notifications()->where('id', $id)->delete();
        }
        return response()->json(['message' => 'Deleted.']);
    }

    private static function notificationTitle(array $data): string
    {
        return match ($data['type'] ?? null) {
            'mention' => 'New mention',
            default   => (string) ($data['title'] ?? 'Notification'),
        };
    }

    /**
     * Human sentence for every notification type. Mention markup
     * (@[Name]{uuid}) is never shown raw.
     */
    private static function notificationMessage(array $data): string
    {
        $plain = fn ($v) => trim(preg_replace('/@\[([^\]]+)\]\{[a-f0-9\-]{36}\}/i', '@$1', (string) $v));

        switch ($data['type'] ?? null) {
            case 'mention':
                $who   = $plain($data['commenter_name'] ?? '') ?: 'Someone';
                $entry = $plain($data['entry_description'] ?? '');
                $book  = $plain($data['book_name'] ?? '');

                $sentence = $entry !== ''
                    ? "{$who} mentioned you in a comment on '{$entry}'"
                    : "{$who} mentioned you in a comment";

                return $book !== '' ? "{$sentence} in {$book}." : "{$sentence}.";

            default:
                foreach (['message', 'body', 'title'] as $key) {
                    if (! empty($data[$key]) && is_string($data[$key])) {
                        return $plain($data[$key]);
                    }
                }

                return 'You have a new notification.';
        }
    }
}
