<?php

namespace App\Contracts\Store;

interface SmsSender
{
    public function send(string $to, string $message): void;
}
