<?php

namespace App\Services\SystemErrors;

use App\Models\SystemError;
use App\Services\Notifications\TopbarNotificationService;
use Error;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class SystemErrorCaptureService
{
    private static bool $capturing = false;

    public function __construct(
        private readonly SystemErrorSanitizer $sanitizer,
    ) {}

    public function capture(Throwable $exception): ?SystemError
    {
        if (self::$capturing || $this->shouldIgnore($exception)) {
            return null;
        }

        self::$capturing = true;

        try {
            if (! Schema::hasTable('system_errors')) {
                return null;
            }

            $now = now();
            $message = $this->sanitizer->message($exception->getMessage()) ?: class_basename($exception);
            $file = $this->sanitizer->file($exception->getFile());
            $httpStatus = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : null;
            $fingerprint = $this->fingerprint($exception, $message, $file);
            $attributes = $this->attributes($exception, $message, $file, $httpStatus);

            $error = DB::transaction(function () use ($fingerprint, $attributes, $now): SystemError {
                $inserted = DB::table('system_errors')->insertOrIgnore([
                    ...$attributes,
                    'context' => json_encode($attributes['context'] ?? [], JSON_THROW_ON_ERROR),
                    'reference' => (string) Str::ulid(),
                    'fingerprint' => $fingerprint,
                    'status' => SystemError::STATUS_OPEN,
                    'occurrences' => 1,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $error = SystemError::query()
                    ->where('fingerprint', $fingerprint)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inserted === 0) {
                    $error->update([
                        ...$attributes,
                        'status' => SystemError::STATUS_OPEN,
                        'occurrences' => DB::raw('occurrences + 1'),
                        'last_seen_at' => $now,
                        'resolved_at' => null,
                        'resolved_by_id' => null,
                        'resolution_type' => null,
                        'resolution_note' => null,
                        'verified_at' => null,
                    ]);
                }

                return $error->fresh();
            });

            TopbarNotificationService::invalidateSystemErrors();

            return $error;
        } catch (Throwable) {
            return null;
        } finally {
            self::$capturing = false;
        }
    }

    public function shouldIgnore(Throwable $exception): bool
    {
        if (
            $exception instanceof ValidationException
            || $exception instanceof AuthenticationException
            || $exception instanceof AuthorizationException
            || $exception instanceof TokenMismatchException
            || $exception instanceof ModelNotFoundException
            || $exception instanceof ConnectionException
        ) {
            return true;
        }

        if (
            $exception instanceof Error
            && str_contains($exception->getMessage(), 'Call to undefined function Illuminate\\Filesystem\\exec()')
            && app()->runningInConsole()
            && in_array('storage:link', $_SERVER['argv'] ?? [], true)
        ) {
            return true;
        }

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(
        Throwable $exception,
        string $message,
        ?string $file,
        ?int $httpStatus,
    ): array {
        $request = app()->runningInConsole() ? null : request();
        $route = $request?->route();
        $userId = auth()->id();

        return [
            'source' => 'exception',
            'severity' => $this->severity($exception, $httpStatus),
            'exception_class' => $exception::class,
            'message' => $message,
            'file' => $file,
            'line' => max(0, $exception->getLine()),
            'trace' => $this->sanitizer->trace($exception->getTraceAsString()),
            'request_method' => $request?->method(),
            'request_path' => $this->sanitizer->requestPath($request?->getRequestUri()),
            'route_name' => is_object($route) ? $route->getName() : null,
            'http_status' => $httpStatus,
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'environment' => Str::limit((string) app()->environment(), 50, ''),
            'release' => filled(config('app.release')) ? Str::limit((string) config('app.release'), 100, '') : null,
            'context' => $this->sanitizer->context([
                'sapi' => PHP_SAPI,
                'command' => app()->runningInConsole()
                    ? implode(' ', array_slice($_SERVER['argv'] ?? [], 0, 2))
                    : null,
            ]),
        ];
    }

    private function fingerprint(Throwable $exception, string $message, ?string $file): string
    {
        $normalizedMessage = preg_replace([
            '/\b[0-9a-f]{8}-[0-9a-f-]{27,}\b/i',
            '/\b[0-9A-HJKMNP-TV-Z]{26}\b/',
            '/\b\d+\b/',
        ], ['{uuid}', '{ulid}', '{n}'], $message) ?? $message;

        return hash('sha256', implode('|', [
            $exception::class,
            $normalizedMessage,
            $file ?? '',
            (string) $exception->getLine(),
        ]));
    }

    private function severity(Throwable $exception, ?int $httpStatus): string
    {
        if ($exception instanceof Error || ($httpStatus !== null && $httpStatus >= 503)) {
            return SystemError::SEVERITY_CRITICAL;
        }

        return SystemError::SEVERITY_ERROR;
    }
}
