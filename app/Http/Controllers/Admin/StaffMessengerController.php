<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Message;
use App\Models\User;
use App\Services\Communication\DirectStaffMessengerService;
use App\Services\Communication\StaffGroupMessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class StaffMessengerController extends Controller
{
    public function staff(
        Request $request,
        DirectStaffMessengerService $messenger,
        StaffGroupMessengerService $groups,
    ): JsonResponse {
        $user = $this->adminUser($request);
        $messenger->ensureChannel();

        $groupRows = $groups->listGroups($user);

        return response()->json([
            'staff' => $messenger->searchableStaff($user, null, 100),
            'groups' => $groupRows,
            'unread' => $messenger->totalUnread($user) + (int) collect($groupRows)->sum('unread'),
        ]);
    }

    public function messages(
        Request $request,
        User $peer,
        DirectStaffMessengerService $messenger,
        StaffGroupMessengerService $groups,
    ): JsonResponse {
        $user = $this->adminUser($request);

        if ($peer->id === $user->id || $peer->status !== User::STATUS_ACTIVE || ! $peer->canUseAdmin()) {
            return response()->json([
                'message' => __('communication.messenger.errors.peer'),
            ], 422);
        }

        $messenger->ensureChannel();
        $existing = $messenger->findDm($messenger->pairKey($user->id, $peer->id));
        $rows = [];

        if ($existing !== null) {
            $rows = Message::query()
                ->with('participant')
                ->where('conversation_id', $existing->id)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->limit(200)
                ->get()
                ->map(fn (Message $message): array => [
                    'id' => (int) $message->id,
                    'body' => (string) $message->body,
                    'mine' => $message->participant
                        && $message->participant->actor_type === 'user'
                        && (int) $message->participant->actor_id === (int) $user->id,
                    'sender' => null,
                    'time' => $message->created_at?->timezone(config('app.timezone'))->format('H:i'),
                ])
                ->values()
                ->all();

            try {
                $messenger->markExistingDmRead($user, $existing);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'peer' => [
                'id' => (int) $peer->id,
                'name' => (string) $peer->name,
                'email' => (string) $peer->email,
                'online' => $messenger->onlineUserIds()->contains($peer->id),
            ],
            'messages' => $rows,
            'unread' => $messenger->totalUnread($user) + $groups->totalUnread($user),
        ]);
    }

    public function send(Request $request, DirectStaffMessengerService $messenger): JsonResponse
    {
        $user = $this->adminUser($request);

        $data = $request->validate([
            'peer_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $peer = User::query()
            ->whereKey((int) $data['peer_id'])
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->with('role')
            ->first();

        if ($peer === null || ! $peer->canUseAdmin() || $peer->id === $user->id) {
            return response()->json([
                'message' => __('communication.messenger.errors.peer'),
            ], 422);
        }

        try {
            $message = $messenger->postMessage($user, $peer, (string) $data['body']);
        } catch (ValidationException $exception) {
            $first = collect($exception->errors())->flatten()->first()
                ?: __('communication.messenger.errors.body');

            return response()->json(['message' => $first], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('communication.messenger.errors.forbidden'),
            ], 403);
        }

        return response()->json([
            'message' => [
                'id' => (int) $message->id,
                'body' => (string) $message->body,
                'mine' => true,
                'sender' => null,
                'time' => $message->created_at?->timezone(config('app.timezone'))->format('H:i'),
            ],
        ]);
    }

    public function createGroup(Request $request, StaffGroupMessengerService $groups): JsonResponse
    {
        $user = $this->adminUser($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'member_ids' => ['required', 'array', 'min:2', 'max:5'],
            'member_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        try {
            $conversation = $groups->createGroup(
                $user,
                (string) $data['title'],
                array_map('intval', $data['member_ids']),
            );
        } catch (ValidationException $exception) {
            $first = collect($exception->errors())->flatten()->first()
                ?: __('communication.messenger.errors.group_members');

            return response()->json(['message' => $first], 422);
        }

        return response()->json([
            'group' => [
                'id' => (int) $conversation->id,
                'title' => (string) $conversation->subject,
                'unread' => 0,
                'member_count' => $groups->memberNames($conversation)->count(),
                'members' => $groups->memberNames($conversation)->take(4)->values()->all(),
            ],
        ], 201);
    }

    public function groupMessages(
        Request $request,
        int $conversation,
        StaffGroupMessengerService $groups,
        DirectStaffMessengerService $messenger,
    ): JsonResponse {
        $user = $this->adminUser($request);
        $model = $groups->findGroupFor($user, $conversation);

        if ($model === null) {
            return response()->json([
                'message' => __('communication.messenger.errors.group'),
            ], 422);
        }

        $rows = $groups->messagesFor($user, $model, markRead: true);

        return response()->json([
            'group' => [
                'id' => (int) $model->id,
                'title' => (string) ($model->subject ?: __('communication.messenger.group_fallback')),
                'members' => $groups->memberNames($model)->values()->all(),
            ],
            'messages' => $rows,
            'unread' => $messenger->totalUnread($user) + $groups->totalUnread($user),
        ]);
    }

    public function sendGroup(
        Request $request,
        int $conversation,
        StaffGroupMessengerService $groups,
    ): JsonResponse {
        $user = $this->adminUser($request);
        $model = $groups->findGroupFor($user, $conversation);

        if ($model === null) {
            return response()->json([
                'message' => __('communication.messenger.errors.group'),
            ], 422);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $message = $groups->postMessage($user, $model, (string) $data['body']);
        } catch (ValidationException $exception) {
            $first = collect($exception->errors())->flatten()->first()
                ?: __('communication.messenger.errors.body');

            return response()->json(['message' => $first], 422);
        }

        return response()->json([
            'message' => [
                'id' => (int) $message->id,
                'body' => (string) $message->body,
                'mine' => true,
                'sender' => null,
                'time' => $message->created_at?->timezone(config('app.timezone'))->format('H:i'),
            ],
        ]);
    }

    private function adminUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->canUseAdmin(), 403);

        return $user;
    }
}
