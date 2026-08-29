<?php

namespace App\Services\Notifications;

class TopbarNotificationService
{
    public function __construct(
        private readonly ModuleNotificationService $modules,
    ) {}

    /**
     * @return array{total: int, items: list<array{key: string, label: string, meta: string, count: int, url: string}>, inbox_unread: int, nav_modules: array<string, int>}
     */
    public function summary(): array
    {
        return $this->modules->bellSummary();
    }

    public static function invalidateSystemErrors(): void
    {
        ModuleNotificationService::invalidateSystemErrors();
    }
}
