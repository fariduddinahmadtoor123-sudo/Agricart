<?php

namespace Tests\Feature\Retention;

use App\Models\StoreInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_retention_redacts_metadata_and_only_deletes_expired_closed_records(): void
    {
        $open = StoreInquiry::query()->create([
            'name' => 'Open',
            'phone' => '03000000000',
            'message' => 'Keep business inquiry',
            'status' => StoreInquiry::STATUS_NEW,
            'ip_hash' => str_repeat('a', 64),
            'user_agent' => 'Browser',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);
        $open->timestamps = false;
        $open->forceFill([
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ])->save();
        $closed = StoreInquiry::query()->create([
            'name' => 'Closed',
            'phone' => '03000000001',
            'message' => 'Expired closed inquiry',
            'status' => StoreInquiry::STATUS_CLOSED,
            'closed_at' => now()->subDays(800),
        ]);

        $this->artisan('store-inquiries:prune', [
            '--metadata-days' => 90,
            '--closed-days' => 730,
        ])->assertSuccessful();

        $this->assertDatabaseHas('store_inquiries', ['id' => $open->id, 'ip_hash' => null, 'user_agent' => null]);
        $this->assertDatabaseMissing('store_inquiries', ['id' => $closed->id]);
    }
}
