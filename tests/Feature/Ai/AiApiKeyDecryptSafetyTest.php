<?php

namespace Tests\Feature\Ai;

use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Catalog\CategoryEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AiApiKeyDecryptSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_corrupt_api_key_does_not_throw_and_enrichment_asks_to_reenter(): void
    {
        $config = AiConfiguration::query()->create([
            'provider' => 'openrouter',
            'api_key' => 'temporary',
            'model_id' => 'test/model',
            'model_name' => 'Test',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        // Simulate ciphertext encrypted with a different APP_KEY (hosting restore).
        \DB::table('ai_configurations')->where('id', $config->id)->update([
            'api_key' => 'eyJpdiI6ImZha2UiLCJ2YWx1ZSI6ImZha2UiLCJtYWMiOiJmYWtlIn0=',
        ]);

        $fresh = AiConfiguration::query()->findOrFail($config->id);
        $this->assertTrue($fresh->hasCorruptApiKey());
        $this->assertNull($fresh->api_key);

        $cleared = app(AiConfigurationService::class)->sanitizeUnreadableApiKeys();
        $this->assertSame(1, $cleared);

        $this->assertNull(app(AiConfigurationService::class)->active());

        $result = app(CategoryEnrichmentService::class)->enrich('Seeds', null);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Settings', $result['message']);
    }

    public function test_valid_api_key_still_decrypts(): void
    {
        $config = AiConfiguration::query()->create([
            'provider' => 'openrouter',
            'api_key' => 'sk-or-v1-test-key',
            'model_id' => 'test/model',
            'model_name' => 'Test',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        $fresh = $config->fresh();
        $this->assertSame('sk-or-v1-test-key', $fresh->api_key);
        $this->assertFalse($fresh->hasCorruptApiKey());
        $this->assertTrue($fresh->hasUsableApiKey());

        // Ciphertext must be decryptable with Crypt::decrypt (Laravel encrypted cast format).
        $this->assertSame('sk-or-v1-test-key', Crypt::decrypt($fresh->getRawOriginal('api_key')));
    }
}
