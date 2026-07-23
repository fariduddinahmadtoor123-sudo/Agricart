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
    /**
     * @var array<string, array{
     *     positive_en: string,
     *     positive_ur: string,
     *     neutral_en: string,
     *     neutral_ur: string,
     *     needs_improvement_en: string,
     *     needs_improvement_ur: string
     * }>
     */
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
                'positive_en' => $row['positive_en'],
                'positive_ur' => $row['positive_ur'],
                'neutral_en' => $row['neutral_en'],
                'neutral_ur' => $row['neutral_ur'],
                'needs_improvement_en' => $row['needs_improvement_en'],
                'needs_improvement_ur' => $row['needs_improvement_ur'],
            ];
        }
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canUseAdmin();
    }
}
