<?php

namespace Tests\Feature\Security;

use App\Enums\AiLogStatus;
use App\Enums\AiLogType;
use App\Livewire\ActivityLogs\Detail as ActivityLogDetail;
use App\Livewire\ActivityLogs\ListLogs as ActivityLogList;
use App\Livewire\AiLogs\Detail as AiLogDetail;
use App\Livewire\AiLogs\ListLogs as AiLogList;
use App\Livewire\AiSettings\Form as AiSettingsForm;
use App\Livewire\AiSettings\Manager as AiSettingsManager;
use App\Livewire\CompanyProfile\Form as CompanyProfileForm;
use App\Livewire\Finance\FundTransferForm;
use App\Livewire\Sales\Form as SalesForm;
use App\Livewire\SaleReturns\Form as SaleReturnForm;
use App\Livewire\SecretCodings\Form as SecretCodingForm;
use App\Livewire\Store\HeaderSettings;
use App\Livewire\Store\FooterSettings;
use App\Livewire\Store\MenuManager;
use App\Livewire\Store\OverviewSettings;
use App\Livewire\Store\PagesManager;
use App\Livewire\Store\TickerManager;
use App\Livewire\Store\TopBarSettings;
use App\Livewire\Users\Form as UserForm;
use App\Models\AiLog;
use App\Models\AiConfiguration;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Rules\SafeUrl;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Access\UserEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseOneAuditRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_globally_mounted_sensitive_components_are_harmless_but_actions_forbid_access(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => null]);
        $this->actingAs($user);

        Livewire::test(AiSettingsForm::class)->assertOk()->call('loadModels')->assertForbidden();
        Livewire::test(AiSettingsManager::class)->assertOk()->call('pauseAi')->assertForbidden();
        Livewire::test(AiLogDetail::class)->assertOk()->call('loadLog', 1)->assertForbidden();
        Livewire::test(AiLogList::class)->assertOk()->call('updateSearch', 'secret')->assertForbidden();
        Livewire::test(ActivityLogDetail::class)->assertOk()->call('loadLog', 1)->assertForbidden();
        Livewire::test(ActivityLogList::class)->assertOk()->call('updateSearch', 'secret')->assertForbidden();
        Livewire::test(UserForm::class)->assertOk()->call('openForm')->assertForbidden();
        Livewire::test(CompanyProfileForm::class)->assertOk()->call('openForm')->assertForbidden();
        Livewire::test(SecretCodingForm::class)->assertOk()->call('openForm')->assertForbidden();
        Livewire::test(FundTransferForm::class)->assertOk()->call('openForm')->assertForbidden();
        Livewire::test(SalesForm::class)->assertOk()->call('openForm')->assertForbidden();
        Livewire::test(SaleReturnForm::class)->assertOk()->call('openForm')->assertForbidden();
        Livewire::test(SaleReturnForm::class)->assertOk()->call('loadInvoice')->assertForbidden();
        Livewire::test(HeaderSettings::class)->assertOk()->call('save')->assertForbidden();
        Livewire::test(FooterSettings::class)->assertOk()->call('saveSettings')->assertForbidden();
        Livewire::test(MenuManager::class)->assertOk()->call('saveSettings')->assertForbidden();
        Livewire::test(OverviewSettings::class)->assertOk()->call('save')->assertForbidden();
        Livewire::test(PagesManager::class)->assertOk()->call('savePage')->assertForbidden();
        Livewire::test(TickerManager::class)->assertOk()->call('saveSettings')->assertForbidden();
        Livewire::test(TopBarSettings::class)->assertOk()->call('save')->assertForbidden();
    }

    public function test_payment_proofs_are_private_and_only_owner_or_authorized_admin_can_read_them(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = $this->customer('proof-owner@example.test');
        $other = $this->customer('proof-other@example.test');
        $order = $this->order($owner);

        app(OnlineSaleOrderService::class)->submitCustomerPaymentProof($order, $owner, [
            'bank_receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $order->refresh();
        Storage::disk('local')->assertExists($order->customer_bank_receipt_path);
        Storage::disk('public')->assertMissing($order->customer_bank_receipt_path);

        $this->actingAs($owner, 'customer')
            ->get(route('store.customer.orders.payment-proof.show', $order->order_number))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($other, 'customer')
            ->get(route('store.customer.orders.payment-proof.show', $order->order_number))
            ->assertNotFound();

        $unauthorizedAdmin = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => null]);
        $this->actingAs($unauthorizedAdmin)
            ->get(route('admin.online-sale-orders.payment-proof.show', $order))
            ->assertForbidden();
    }

    public function test_checkout_controller_stores_new_payment_proofs_on_private_local_disk(): void
    {
        $source = File::get(app_path('Http/Controllers/Store/CheckoutController.php'));

        $this->assertStringContainsString("'online-orders/customer-receipts/' . \$order->id", $source);
        $this->assertStringContainsString("'local',", $source);
        $this->assertStringNotContainsString("'online-orders/customer-receipts/' . \$order->id,\n                    'public'", $source);
    }

    public function test_legacy_public_payment_proofs_remain_available_and_backfill_is_idempotent(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $customer = $this->customer('legacy-proof@example.test');
        $order = $this->order($customer);
        $path = "online-orders/customer-receipts/{$order->id}/legacy.pdf";
        Storage::disk('public')->put($path, 'legacy-proof');
        $order->update(['customer_bank_receipt_path' => $path]);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.payment-proof.show', $order->order_number))
            ->assertOk();

        $this->artisan('online-orders:backfill-private-payment-proofs')->assertSuccessful();
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertExists($path);

        $this->artisan('online-orders:backfill-private-payment-proofs', ['--delete-public' => true])
            ->assertSuccessful();
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_payment_proof_backfill_never_deletes_an_unverified_public_original(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $customer = $this->customer('mismatch-proof@example.test');
        $order = $this->order($customer);
        $path = "online-orders/customer-receipts/{$order->id}/mismatch.pdf";
        Storage::disk('public')->put($path, 'public-original');
        Storage::disk('local')->put($path, 'different-private-copy');
        $order->update(['customer_bank_receipt_path' => $path]);

        $this->artisan('online-orders:backfill-private-payment-proofs', ['--delete-public' => true])
            ->assertFailed();
        Storage::disk('public')->assertExists($path);

        config(['security.legacy_public_payment_proof_fallback' => false]);
        Storage::disk('local')->delete($path);
        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.payment-proof.show', $order->order_number))
            ->assertNotFound();
    }

    public function test_locale_redirect_rejects_network_path_and_backslash_redirects(): void
    {
        $this->get('/locale/en?redirect='.urlencode('//evil.example/path'))->assertRedirect('/');
        $this->get('/locale/en?redirect='.urlencode('/\\evil.example/path'))->assertRedirect('/');
        $this->get('/locale/en?redirect='.urlencode('/account/orders'))->assertRedirect('/account/orders');
    }

    public function test_named_throttles_cover_customer_mutation_endpoints(): void
    {
        foreach ([
            'store.customer.login.submit' => 'throttle:customer-login',
            'store.customer.register.submit' => 'throttle:customer-register',
            'store.customer.password.email' => 'throttle:customer-password-reset',
            'store.customer.password.update' => 'throttle:customer-password-reset',
            'store.checkout.place' => 'throttle:store-checkout',
            'store.customer.orders.payment-proof' => 'throttle:customer-payment-proof',
        ] as $routeName => $middleware) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }

    public function test_store_urls_reject_unsafe_schemes(): void
    {
        $rule = new SafeUrl;

        $this->assertTrue(Validator::make(['url' => '/safe/path'], ['url' => [$rule]])->passes());
        $this->assertTrue(Validator::make(['url' => 'https://example.test/path'], ['url' => [$rule]])->passes());
        $this->assertFalse(Validator::make(['url' => 'javascript:alert(1)'], ['url' => [$rule]])->passes());
        $this->assertFalse(Validator::make(['url' => '//evil.example/path'], ['url' => [$rule]])->passes());
    }

    public function test_ai_log_retention_prunes_old_records_only(): void
    {
        $old = $this->aiLog(now()->subDays(91));
        $recent = $this->aiLog(now()->subDays(10));

        $this->artisan('ai-logs:prune --days=90')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_user_ai_payload_excludes_phone_account_and_address_values(): void
    {
        $form = new UserForm;
        $form->name = 'Safe Name';
        $form->address = 'Full private street address';
        $form->phoneRows = [[
            'contact_person' => 'Contact',
            'contact_person_ur' => '',
            'phone_number' => '03009998888',
        ]];
        $form->bankRows = [[
            'bank_name' => 'Test Bank',
            'account_title' => 'Safe Name',
            'account_title_ur' => '',
            'account_number' => 'PK00SECRETACCOUNT',
        ]];

        $method = new \ReflectionMethod($form, 'enrichmentPayload');
        $encoded = json_encode($method->invoke($form));

        $this->assertStringNotContainsString('Full private street address', $encoded);
        $this->assertStringNotContainsString('03009998888', $encoded);
        $this->assertStringNotContainsString('PK00SECRETACCOUNT', $encoded);
    }

    public function test_user_ai_request_and_log_do_not_store_sensitive_contact_values(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'name_en' => 'Safe Name',
                            'name_ur' => 'محفوظ نام',
                            'phone_rows' => [],
                            'bank_rows' => [],
                        ]),
                    ],
                ]],
            ]),
        ]);

        AiConfiguration::query()->create([
            'provider' => 'openrouter',
            'api_key' => 'test-api-key',
            'model_id' => 'test/model',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        $result = app(UserEnrichmentService::class)->enrich([
            'name_en' => 'Safe Name',
            'name_ur' => '',
            'address_en' => 'Full private street address',
            'phone_rows' => [[
                'contact_person' => 'Contact',
                'contact_person_ur' => '',
                'phone_number' => '03009998888',
            ]],
            'bank_rows' => [[
                'bank_name' => 'Test Bank',
                'account_title' => 'Safe Name',
                'account_title_ur' => '',
                'account_number' => 'PK00SECRETACCOUNT',
            ]],
        ]);

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request): bool {
            $body = $request->body();

            return ! str_contains($body, '03009998888')
                && ! str_contains($body, 'PK00SECRETACCOUNT')
                && ! str_contains($body, 'Full private street address');
        });

        $log = AiLog::query()->where('type', AiLogType::UserEnrichment->value)->firstOrFail();
        $this->assertNull($log->prompt);
        $this->assertNull($log->response);
        $this->assertTrue((bool) data_get($log->metadata, 'content_minimized'));
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.random_int(10000, 99999),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $email,
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }

    private function order(Customer $customer): OnlineSaleOrder
    {
        return OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-'.random_int(10000, 99999),
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_AWAITING,
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 0,
        ]);
    }

    private function aiLog(\DateTimeInterface $createdAt): AiLog
    {
        return AiLog::query()->create([
            'type' => AiLogType::Prompt,
            'status' => AiLogStatus::Success,
            'source' => 'test',
            'created_at' => $createdAt,
        ]);
    }
}
