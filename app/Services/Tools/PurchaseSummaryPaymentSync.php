<?php

namespace App\Services\Tools;

use App\Livewire\Tools\ReOrderSettingsForm;
use App\Models\ToolsPurchaseSummary;
use App\Services\PricingControls\SecretCodingEngine;

class PurchaseSummaryPaymentSync
{
    /**
     * Attach a paid line to the user's open Purchase Summary, or create one.
     *
     * @return array{summary_no: string, created: bool}
     */
    public function attachPaidLine(int $userId, string $shopkeeper, string $amountRaw, string $payDate, string $lang = 'en'): array
    {
        $shopkeeper = trim($shopkeeper);
        $amountRaw = trim($amountRaw);
        if ($shopkeeper === '' || $amountRaw === '') {
            return ['summary_no' => '', 'created' => false];
        }

        app(PurchaseSummarySettingsService::class)->rememberShopkeeper($shopkeeper, $userId);

        $encoded = $this->encodeAmount($amountRaw, $userId);
        $open = ToolsPurchaseSummary::query()
            ->where('user_id', $userId)
            ->where('status', 'saved')
            ->latest('updated_at')
            ->first();

        if ($open) {
            $cells = is_array($open->cells) ? $open->cells : [];
            $row = $this->nextEmptyRow($cells);
            $cells[(string) $row] = [
                'payment' => $encoded,
                'shopkeeper' => $shopkeeper,
                'status' => '',
            ];
            $pageCount = max(1, (int) ($cells['_pageCount'] ?? 1));
            $needed = $this->pageCountForRow($row);
            if ($needed > $pageCount) {
                $pageCount = $needed;
            }
            $cells['_pageCount'] = $pageCount;
            $open->update(['cells' => $cells]);

            return ['summary_no' => (string) $open->summary_no, 'created' => false];
        }

        $reorder = app(ReOrderSettingsService::class)->getForUser($userId);
        $summaryNo = app(PurchaseSummarySettingsService::class)->nextSummaryNumber($userId);
        $user = \App\Models\User::query()->find($userId);
        $lang = in_array($lang, ['en', 'ur'], true) ? $lang : 'en';
        $shopName = $lang === 'ur'
            ? (string) (($reorder['shopNameUr'] ?? '') !== '' ? $reorder['shopNameUr'] : ($reorder['shopNameEn'] ?? ''))
            : (string) (($reorder['shopNameEn'] ?? '') !== '' ? $reorder['shopNameEn'] : ($reorder['shopNameUr'] ?? ''));
        $shopAddress = $lang === 'ur'
            ? (string) (($reorder['addressUr'] ?? '') !== '' ? $reorder['addressUr'] : ($reorder['addressEn'] ?? ''))
            : (string) (($reorder['addressEn'] ?? '') !== '' ? $reorder['addressEn'] : ($reorder['addressUr'] ?? ''));
        $phones = is_array($reorder['phones'] ?? null) ? $reorder['phones'] : [];
        if ($phones === [] && filled($reorder['phone'] ?? null)) {
            $phones = [(string) $reorder['phone']];
        }

        ToolsPurchaseSummary::query()->create([
            'user_id' => $userId,
            'summary_no' => $summaryNo,
            'summary_date' => $payDate !== '' ? $payDate : now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y'),
            'lang' => $lang,
            'shop_name' => $shopName,
            'shop_address' => $shopAddress,
            'shop_phones' => array_values(array_filter(array_map('strval', $phones))),
            'prepared_by' => (string) ($user?->name ?? ''),
            'cells' => [
                '1' => [
                    'payment' => $encoded,
                    'shopkeeper' => $shopkeeper,
                    'status' => '',
                ],
                '_pageCount' => 1,
            ],
            'payment_sources' => [],
            'pending_bills' => [],
            'status' => 'saved',
        ]);

        return ['summary_no' => $summaryNo, 'created' => true];
    }

    protected function encodeAmount(string $value, int $userId): string
    {
        $normalized = trim(ReOrderSettingsForm::normalizeDigits($value));
        if ($normalized === '') {
            return '';
        }
        $slots = app(ReOrderSettingsService::class)->getForUser($userId)['digitSlots'] ?? [];
        if (! is_array($slots) || $slots === []) {
            return $normalized;
        }
        $hasMapping = false;
        foreach ($slots as $token) {
            if (trim((string) $token) !== '') {
                $hasMapping = true;
                break;
            }
        }
        if (! $hasMapping) {
            return $normalized;
        }

        return app(SecretCodingEngine::class)->encodeWithSlots($normalized, $slots);
    }

    /**
     * @param  array<string, mixed>  $cells
     */
    protected function nextEmptyRow(array $cells): int
    {
        $max = 0;
        for ($i = 1; $i <= 500; $i++) {
            $row = $cells[(string) $i] ?? null;
            if (! is_array($row)) {
                return $i;
            }
            $payment = trim((string) ($row['payment'] ?? ''));
            $shopkeeper = trim((string) ($row['shopkeeper'] ?? ''));
            if ($payment === '' && $shopkeeper === '') {
                return $i;
            }
            $max = $i;
        }

        return $max + 1;
    }

    protected function pageCountForRow(int $row): int
    {
        $first = 25 * 2;
        if ($row <= $first) {
            return 1;
        }
        $cont = 25 * 2;

        return min(20, 1 + (int) ceil(($row - $first) / $cont));
    }
}
