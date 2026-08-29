<?php

namespace Tests\Feature\Purchasing;

use App\Models\PurchasePaymentSummary;
use App\Models\SecretCodingProfile;
use App\Models\User;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\PricingControls\SecretCodingService;
use App\Services\Purchasing\PurchasePaymentSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePaymentSummarySecretCodingTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_summary_totals_stay_correct_after_active_coding_changes(): void
    {
        $oldProfile = $this->makeProfile('Old Coding', [
            '0' => 'Q',
            '1' => 'W',
            '2' => 'E',
            '3' => 'R',
            '4' => 'T',
            '5' => 'Y',
            '6' => 'U',
            '7' => 'I',
            '8' => 'O',
            '9' => 'P',
        ]);

        $engine = app(SecretCodingEngine::class);
        $service = app(PurchasePaymentSummaryService::class);

        $lineOne = $engine->encodePrice('1500', $oldProfile);
        $lineTwo = $engine->encodePrice('2500', $oldProfile);
        $sourceOne = $engine->encodePrice('4000', $oldProfile);

        $summary = $service->create([
            'summary_date' => '2026-08-29',
            'paper_size' => 'a4',
            'locale' => 'en',
            'purchaser_id' => User::factory()->create()->id,
            'payment_sources' => [
                ['label' => 'Cash', 'amount' => $sourceOne],
            ],
            'items' => [
                [
                    'supplier_id' => null,
                    'is_manual' => true,
                    'name_en' => 'Supplier A',
                    'name_ur' => '',
                    'phone' => '',
                    'address' => '',
                    'payment_amount' => $lineOne,
                    'line_status' => '',
                ],
                [
                    'supplier_id' => null,
                    'is_manual' => true,
                    'name_en' => 'Supplier B',
                    'name_ur' => '',
                    'phone' => '',
                    'address' => '',
                    'payment_amount' => $lineTwo,
                    'line_status' => '',
                ],
            ],
        ]);

        $summary->forceFill(['secret_coding_profile_id' => $oldProfile->id])->saveQuietly();

        $newProfile = $this->makeProfile('New Coding', [
            '0' => 'A',
            '1' => 'B',
            '2' => 'C',
            '3' => 'D',
            '4' => 'E',
            '5' => 'F',
            '6' => 'G',
            '7' => 'H',
            '8' => 'J',
            '9' => 'K',
        ]);

        $oldProfile->update(['status' => 'inactive']);
        $newProfile->update(['status' => 'active']);

        $summary = PurchasePaymentSummary::query()->with('items')->findOrFail($summary->id);

        $this->assertSame(4000.0, $summary->totalPayments());
        $this->assertSame(4000.0, $summary->totalSourcePayments());
        $this->assertSame(0.0, $summary->balanceRemaining());
        $this->assertSame($oldProfile->id, $service->resolveCodingProfile($summary)?->id);
        $this->assertSame(
            $engine->encodePrice('4000', $oldProfile),
            $service->formatEncodedAmount($summary->totalPayments(), $service->resolveCodingProfile($summary)),
        );
    }

    public function test_new_summary_uses_current_active_coding_profile(): void
    {
        $profile = $this->makeProfile('Current Coding', [
            '0' => 'M',
            '1' => 'N',
            '2' => 'X',
            '3' => 'Z',
            '4' => 'V',
            '5' => 'C',
            '6' => 'L',
            '7' => 'H',
            '8' => 'J',
            '9' => 'K',
        ]);

        $engine = app(SecretCodingEngine::class);
        $encoded = $engine->encodePrice('500', $profile);

        $summary = app(PurchasePaymentSummaryService::class)->create([
            'summary_date' => '2026-08-29',
            'paper_size' => 'a4',
            'locale' => 'en',
            'purchaser_id' => User::factory()->create()->id,
            'payment_sources' => [
                ['label' => 'Cash', 'amount' => $encoded],
            ],
            'items' => [
                [
                    'supplier_id' => null,
                    'is_manual' => true,
                    'name_en' => 'Supplier A',
                    'name_ur' => '',
                    'phone' => '',
                    'address' => '',
                    'payment_amount' => $encoded,
                    'line_status' => '',
                ],
            ],
        ]);

        $this->assertSame($profile->id, $summary->fresh()->secret_coding_profile_id);
        $this->assertSame(500.0, $summary->fresh(['items'])->totalPayments());
    }

    /**
     * @param  array<string, string>  $slots
     */
    private function makeProfile(string $name, array $slots): SecretCodingProfile
    {
        app(SecretCodingService::class)->ensureDefaultProfile();
        SecretCodingProfile::query()->where('status', 'active')->update(['status' => 'inactive']);

        return SecretCodingProfile::query()->create([
            'profile_number' => 'SC-'.random_int(100, 999),
            'name_en' => $name,
            'status' => 'active',
            'digit_slots' => SecretCodingService::normalizeDigitSlotsStatic($slots),
        ]);
    }
}
