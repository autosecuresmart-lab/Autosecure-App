<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Customer notification centre.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::query()
            ->where('user_id', $user->getKey())
            ->when(
                $request->boolean('unread_only'),
                fn ($query) => $query->unread(),
            )
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        return response()->json([
            'data' => $notifications->getCollection()->map(fn (Notification $n) => [
                'uuid' => $n->uuid,
                'type' => $n->type,
                'category' => $n->category,
                'title' => $n->title,
                'body' => $n->body,
                'channel' => $n->channel,
                'data' => $n->data,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread' => Notification::query()
                    ->where('user_id', $user->getKey())
                    ->unread()
                    ->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless(
            (int) $notification->user_id === (int) $request->user()->getKey(),
            403,
            'This notification does not belong to you.',
        );

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
                'status' => Notification::STATUS_READ,
            ])->save();
        }

        return response()->json(['message' => 'Marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::query()
            ->where('user_id', $request->user()->getKey())
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => Notification::STATUS_READ,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'updated' => $updated,
        ]);
    }

    /**
     * Register this handset for push notifications.
     *
     * Tokens are unique per install: re-registering reassigns the token to the
     * currently signed-in customer, which is what should happen when a phone
     * changes hands or a different account signs in.
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'provider' => ['nullable', 'string', 'max:32'],
        ]);

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->getKey(),
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'push_provider' => $data['provider'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Push notifications enabled for this device.',
            'push_token' => [
                'uuid' => $deviceToken->uuid,
                'platform' => $deviceToken->platform,
            ],
        ], 201);
    }

    /**
     * Stop sending push notifications to this handset.
     */
    public function unregisterPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $deleted = DeviceToken::query()
            ->where('token', $data['token'])
            ->where('user_id', $request->user()->getKey())
            ->delete();

        return response()->json([
            'message' => 'Push notifications disabled for this device.',
            'deleted' => $deleted,
        ]);
    }
}
