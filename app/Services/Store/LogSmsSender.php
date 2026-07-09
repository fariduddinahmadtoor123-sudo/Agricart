<?php

namespace App\Services\Store;

use App\Contracts\Store\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::channel('single')->info('SMS (log driver)', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
