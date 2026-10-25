<?php

namespace App\Services\Tools;

use App\Models\ToolsReorderSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ReOrderSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function getForUser(?int $userId = null): array
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);

        if ($userId > 0) {
            $row = ToolsReorderSetting::query()->where('user_id', $userId)->first();
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
            ToolsReorderSetting::query()->updateOrCreate(
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

    protected function filePath(int $userId): string
    {
        return "tools-reorder/settings-{$userId}.json";
    }

    protected function cacheKey(int $userId): string
    {
        return "agricart.tools.reorder.settings.{$userId}";
    }

    public function nextOrderNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $key = "agricart.tools.reorder.order_seq.{$userId}";
        $seq = (int) Cache::get($key, 0) + 1;
        Cache::forever($key, $seq);

        return 'PO-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function peekOrderNumber(?int $userId = null): string
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $seq = (int) Cache::get("agricart.tools.reorder.order_seq.{$userId}", 0) + 1;

        return 'PO-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Learn a typed detail/entry phrase into the per-user dictionary (newest first).
     *
     * @return list<string>
     */
    public function rememberPhrase(string $text, ?int $userId = null): array
    {
        $text = trim($text);
        if (mb_strlen($text) < 2) {
            return $this->phrasesForUser($userId);
        }

        $userId = $userId ?? (int) (auth()->id() ?? 0);
        $data = $this->getForUser($userId);
        $phrases = $this->normalizePhrases($data['phrases'] ?? []);
        $norm = mb_strtolower($text);

        $phrases = array_values(array_filter(
            $phrases,
            static fn (string $phrase): bool => mb_strtolower($phrase) !== $norm
        ));
        array_unshift($phrases, $text);
        $phrases = array_slice($phrases, 0, 500);

        $data['phrases'] = $phrases;
        $this->saveForUser($data, $userId);

        return $phrases;
    }

    /**
     * @return list<string>
     */
    public function phrasesForUser(?int $userId = null): array
    {
        $data = $this->getForUser($userId);

        return $this->normalizePhrases($data['phrases'] ?? []);
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    public function normalizePhrases(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        $seen = [];

        foreach ($raw as $item) {
            $phrase = trim((string) $item);
            if ($phrase === '' || mb_strlen($phrase) < 2) {
                continue;
            }
            $key = mb_strtolower($phrase);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $phrase;
        }

        return $out;
    }
}

