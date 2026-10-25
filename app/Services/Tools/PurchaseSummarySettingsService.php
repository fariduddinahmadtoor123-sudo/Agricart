<?php

namespace App\Services\Tools;

use App\Models\ToolsPurchaseSummarySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PurchaseSummarySettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function getForUser(?int $userId = null): array
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);

        if ($userId > 0) {
            $row = ToolsPurchaseSummarySetting::query()->where('user_id', $userId)->first();
            if ($row && is_array($row->data)) {
                return $row->data;
            }
        }

        $path = $this->filePath($userId);
        if (Storage::disk('local')->exists($path)) {
            $decoded = json_decode(Storage::disk('local')->get($path), true);
            if (is_array($decoded) && $decoded !== []) {
                if ($userId > 0) {
                    $this->saveForUser($decoded, $userId);
                }

                return $decoded;
            }
        }

        $cached = Cache::get($this->cacheKey($userId), []);

        return is_array($cached) ? $cached : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveForUser(array $data, ?int $userId = null): array
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);

        if ($userId > 0) {
            ToolsPurchaseSummarySetting::query()->updateOrCreate(
                ['user_id' => $userId],
                ['data' => $data],
            );
        }

        Storage::disk('local')->put(
            $this->filePath($userId),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        Cache::forever($this->cacheKey($userId), $data);

        return $data;
    }

    /**
     * @return list<string>
     */
    public function shopkeepersForUser(?int $userId = null): array
    {
        return $this->normalizeList($this->getForUser($userId)['shopkeepers'] ?? []);
    }

    /**
     * @return list<string>
     */
    public function rememberShopkeeper(string $text, ?int $userId = null): array
    {
        $text = trim($text);
        if (mb_strlen($text) < 2) {
            return $this->shopkeepersForUser($userId);
        }

        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $data = $this->getForUser($userId);
        $list = $this->normalizeList($data['shopkeepers'] ?? []);
        $norm = mb_strtolower($text);
        $list = array_values(array_filter($list, static fn (string $n): bool => mb_strtolower($n) !== $norm));
        array_unshift($list, $text);
        $list = array_slice($list, 0, 500);
        $data['shopkeepers'] = $list;
        $this->saveForUser($data, $userId);

        return $list;
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    public function normalizeList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($raw as $item) {
            $name = trim((string) $item);
            if ($name === '' || mb_strlen($name) < 2) {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }

        return $out;
    }

    public function nextSummaryNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $key = "agricart.tools.purchase_summary.seq.{$userId}";
        $seq = (int) Cache::get($key, 0) + 1;
        Cache::forever($key, $seq);

        return 'PS-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function peekSummaryNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $seq = (int) Cache::get("agricart.tools.purchase_summary.seq.{$userId}", 0) + 1;

        return 'PS-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    protected function filePath(int $userId): string
    {
        return "tools-purchase-summary/settings-{$userId}.json";
    }

    protected function cacheKey(int $userId): string
    {
        return "agricart.tools.purchase_summary.settings.{$userId}";
    }
}
