<?php

namespace App\Services\Activity;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class ActivityLogger
{
    private static bool $quiet = false;

    /** @var array<string, true> */
    private static array $createdThisRequest = [];

    /**
     * Reset per-request tracking (also useful between PHPUnit assertions).
     */
    public static function flushRequestState(): void
    {
        self::$quiet = false;
        self::$createdThisRequest = [];
    }

    public function login(User $user): ?ActivityLog
    {
        return $this->write(ActivityEvent::Login, $user, [
            'description' => __('agricart.activity.descriptions.login', ['user' => $this->userLabel($user)]),
            'subject' => $user,
            'subject_label' => $this->userLabel($user),
            'user_id' => $user->id,
        ]);
    }

    public function logout(?User $user): ?ActivityLog
    {
        if (! $user instanceof User) {
            return null;
        }

        return $this->write(ActivityEvent::Logout, $user, [
            'description' => __('agricart.activity.descriptions.logout', ['user' => $this->userLabel($user)]),
            'subject' => $user,
            'subject_label' => $this->userLabel($user),
            'user_id' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function failedLogin(array $credentials): ?ActivityLog
    {
        $email = isset($credentials['email']) ? (string) $credentials['email'] : null;

        return $this->write(ActivityEvent::FailedLogin, null, [
            'description' => __('agricart.activity.descriptions.failed_login', [
                'email' => $email ?: __('agricart.activity.unknown_actor'),
            ]),
            'user_id' => null,
            'subject_label' => $email,
            'properties' => array_filter([
                'email' => $email,
            ]),
        ]);
    }

    public function modelCreated(Model $model): ?ActivityLog
    {
        self::$createdThisRequest[$this->modelKey($model)] = true;

        return $this->modelEvent(ActivityEvent::Created, $model, [
            'attributes' => $this->redact($model->getAttributes()),
        ]);
    }

    public function modelUpdated(Model $model): ?ActivityLog
    {
        // Create flows often save again in the same request (labels, media sync, etc.).
        // Keep a single Created entry instead of Created + Updated noise.
        if (isset(self::$createdThisRequest[$this->modelKey($model)])) {
            return null;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at'], $changes['created_at']);

        if ($changes === []) {
            return null;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $model->getOriginal($key);
        }

        return $this->modelEvent(ActivityEvent::Updated, $model, [
            'old' => $this->redact($original),
            'attributes' => $this->redact($changes),
        ]);
    }

    public function modelDeleted(Model $model): ?ActivityLog
    {
        unset(self::$createdThisRequest[$this->modelKey($model)]);

        return $this->modelEvent(ActivityEvent::Deleted, $model, [
            'attributes' => $this->redact($model->getAttributes()),
        ]);
    }

    public function onlineQuotationConverted(OnlineSaleOrder $order, Sale $sale, User $actor): ?ActivityLog
    {
        $orderLabel = filled($order->order_number) ? (string) $order->order_number : '#'.$order->getKey();
        $saleLabel = filled($sale->sale_number) ? (string) $sale->sale_number : '#'.$sale->getKey();

        return $this->write(ActivityEvent::Converted, $actor, [
            'description' => __('agricart.activity.descriptions.converted', [
                'order' => $orderLabel,
                'sale' => $saleLabel,
            ]),
            'subject' => $order,
            'subject_label' => $orderLabel,
            'user_id' => $actor->id,
            'properties' => [
                'order_number' => $orderLabel,
                'sale_id' => $sale->id,
                'sale_number' => $saleLabel,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function modelEvent(ActivityEvent $event, Model $model, array $properties): ?ActivityLog
    {
        if (! Auth::guard('web')->check()) {
            return null;
        }

        if (! $this->isAuditable($model)) {
            return null;
        }

        $label = $this->subjectLabel($model);
        $type = class_basename($model);

        return $this->write($event, Auth::guard('web')->user(), [
            'description' => __('agricart.activity.descriptions.'.$event->value, [
                'type' => $this->subjectTypeLabel($model),
                'label' => $label,
            ]),
            'subject' => $model,
            'subject_label' => $label,
            'properties' => [
                ...$properties,
                'model' => $type,
            ],
        ]);
    }

    /**
     * @param  array{
     *     description: string,
     *     subject?: Model|null,
     *     subject_label?: string|null,
     *     user_id?: int|null,
     *     properties?: array<string, mixed>|null,
     * }  $data
     */
    private function write(ActivityEvent $event, ?User $actor, array $data): ?ActivityLog
    {
        if (self::$quiet) {
            return null;
        }

        self::$quiet = true;

        try {
            $subject = $data['subject'] ?? null;

            return ActivityLog::query()->create([
                'user_id' => $data['user_id'] ?? $actor?->id,
                'event' => $event,
                'subject_type' => $subject instanceof Model ? $subject::class : null,
                'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
                'description' => $data['description'],
                'subject_label' => $data['subject_label'] ?? null,
                'properties' => $data['properties'] ?? null,
                'ip_address' => Request::ip(),
                'user_agent' => Str::limit((string) Request::userAgent(), 500, ''),
                'created_at' => now(),
            ]);
        } finally {
            self::$quiet = false;
        }
    }

    private function isAuditable(Model $model): bool
    {
        return in_array($model::class, config('activity.models', []), true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function redact(array $attributes): array
    {
        $keys = array_map('strtolower', config('activity.redact_keys', []));
        $out = [];

        foreach ($attributes as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, $keys, true) || Str::contains($normalized, ['password', 'secret', 'token', 'api_key'])) {
                $out[$key] = '[redacted]';

                continue;
            }

            if (is_string($value) && strlen($value) > 500) {
                $out[$key] = Str::limit($value, 500);

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }

    private function subjectLabel(Model $model): string
    {
        foreach (['name', 'name_en', 'title', 'email', 'code', 'sku', 'slug'] as $attr) {
            $value = $model->getAttribute($attr);

            if (filled($value) && is_scalar($value)) {
                return (string) $value;
            }
        }

        return '#'.$model->getKey();
    }

    private function subjectTypeLabel(Model $model): string
    {
        $key = 'agricart.activity.subjects.'.class_basename($model);
        $label = __($key);

        return $label === $key ? class_basename($model) : $label;
    }

    private function userLabel(User $user): string
    {
        $name = trim((string) ($user->name ?? ''));
        $email = trim((string) ($user->email ?? ''));

        if ($name !== '' && $email !== '') {
            return $name.' ('.$email.')';
        }

        if ($name !== '') {
            return $name;
        }

        if ($email !== '') {
            return $email;
        }

        return '#'.$user->id;
    }

    private function modelKey(Model $model): string
    {
        return $model::class.':'.(string) $model->getKey();
    }
}
