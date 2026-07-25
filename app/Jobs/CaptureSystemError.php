<?php

namespace App\Jobs;

use App\Services\SystemErrors\SystemErrorCaptureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class CaptureSystemError implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $exceptionClass,
        public string $message,
        public string $file,
        public int $line,
        public string $trace,
        public ?int $httpStatus = null,
    ) {}

    public static function fromThrowable(Throwable $exception): self
    {
        return new self(
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            max(0, $exception->getLine()),
            $exception->getTraceAsString(),
            $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : null,
        );
    }

    public function handle(SystemErrorCaptureService $capture): void
    {
        $capture->captureNowFromReport($this);
    }
}
