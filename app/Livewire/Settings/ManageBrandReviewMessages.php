<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\Catalog\BrandAgricartReviewMessageService;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ManageBrandReviewMessages extends Component
{
    /** @var array<string, array{high_en: string, high_ur: string, low_en: string, low_ur: string}> */
    public array $messages = [];

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public bool $saving = false;

    public function mount(BrandAgricartReviewMessageService $service): void
    {
        $this->hydrateFromService($service);
    }

    public function save(BrandAgricartReviewMessageService $service): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->saving = true;
        $this->statusMessage = null;

        try {
            $service->saveAll($this->messages);
            $this->hydrateFromService($service);
            $this->statusMessage = __('agricart.brands.review_messages.saved');
            $this->statusType = 'success';
        } catch (ValidationException $exception) {
            $this->statusMessage = collect($exception->errors())->flatten()->first()
                ?: __('agricart.brands.messages.validation_failed');
            $this->statusType = 'danger';
        } catch (\Throwable $exception) {
            $this->statusMessage = FriendlyError::message($exception);
            $this->statusType = 'danger';
        } finally {
            $this->saving = false;
        }
    }

    public function resetToFactoryDefaults(BrandAgricartReviewMessageService $service): void
    {
        if (! $this->canManage()) {
            return;
        }

        $defaults = \App\Support\Catalog\BrandAgricartReviewMessageDefaults::all();
        $service->saveAll($defaults);
        $this->hydrateFromService($service);
        $this->statusMessage = __('agricart.brands.review_messages.reset');
        $this->statusType = 'success';
    }

    public function render(): View
    {
        return view('livewire.settings.manage-brand-review-messages', [
            'criteriaKeys' => BrandAgricartReviewCriteria::keys(),
            'canManage' => $this->canManage(),
        ]);
    }

    private function hydrateFromService(BrandAgricartReviewMessageService $service): void
    {
        $this->messages = [];

        foreach ($service->listManaged() as $row) {
            $this->messages[$row['criterion_key']] = [
                'high_en' => $row['high_en'],
                'high_ur' => $row['high_ur'],
                'low_en' => $row['low_en'],
                'low_ur' => $row['low_ur'],
            ];
        }
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canUseAdmin();
    }
}
