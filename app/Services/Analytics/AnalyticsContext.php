<?php

namespace App\Services\Analytics;

class AnalyticsContext
{
    private ?string $pageType = null;

    private ?int $entityId = null;

    private ?string $entitySlug = null;

    public function setPage(string $type, ?int $entityId = null, ?string $entitySlug = null): void
    {
        $this->pageType = mb_substr($type, 0, 32);
        $this->entityId = $entityId;
        $this->entitySlug = filled($entitySlug) ? mb_substr((string) $entitySlug, 0, 255) : null;
    }

    /**
     * @return array{page_type: ?string, entity_id: ?int, entity_slug: ?string}
     */
    public function values(): array
    {
        return [
            'page_type' => $this->pageType,
            'entity_id' => $this->entityId,
            'entity_slug' => $this->entitySlug,
        ];
    }
}
