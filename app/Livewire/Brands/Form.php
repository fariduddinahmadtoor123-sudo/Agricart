<?php

namespace App\Livewire\Brands;

use App\Enums\CategoryAiStatus;
use App\Models\Brand;
use App\Services\Catalog\BrandEnrichmentService;
use App\Services\Catalog\BrandService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $brandId = null;

    public string $mode = 'create';

    public ?TemporaryUploadedFile $imageUpload = null;

    public ?string $imagePreviewUrl = null;

    public ?string $storedImagePath = null;

    public string $enrichmentPrompt = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $description_en = '';

    public string $description_ur = '';

    public string $website_url = '';

    public string $code = '';

    public string $slug = '';

    public string $status = 'active';

    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->enrichmentPrompt = BrandEnrichmentService::defaultPrompt();
        $this->code = app(BrandService::class)->nextCode();
    }

    #[On('brand-form-open')]
    public function openForm(?int $brandId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->brandId = $brandId;

        if ($brandId) {
            $this->loadBrand($brandId);
        }
    }

    #[On('brand-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function updatedImageUpload(): void
    {
        $this->validate([
            'imageUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->imageUpload === null) {
            return;
        }

        $filename = pathinfo($this->imageUpload->getClientOriginalName(), PATHINFO_FILENAME);
        $this->persistUploadedImage();

        $cleanName = Str::title(str_replace(['-', '_'], ' ', $filename));

        if (filled($cleanName)) {
            $this->name_en = $cleanName;

            if (! filled($this->slug)) {
                $this->slug = Str::slug($cleanName);
            }
        }
    }

    public function enrichWithAi(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (! filled(trim($this->name_en))) {
            $this->setStatus(__('agricart.brands.ai.name_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        if (! filled(trim($this->enrichmentPrompt))) {
            $this->setStatus(__('agricart.brands.ai.prompt_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();
        $this->persistUploadedImage();

        try {
            $result = app(BrandEnrichmentService::class)->enrich(
                $this->name_en,
                $this->resolveImagePathForAi(),
                $this->enrichmentPrompt,
            );

            if (! $result['success']) {
                $this->ai_status = CategoryAiStatus::Failed->value;
                $this->setStatus($result['message'], 'danger');

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? []);
            $this->ai_status = CategoryAiStatus::Enriched->value;
            $this->setStatus($result['message'], 'success');
        } catch (\Throwable $exception) {
            $this->ai_status = CategoryAiStatus::Failed->value;
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('brands-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('brands-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.brands.form');
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $this->persistUploadedImage();

        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.brands.fields.name_en');
        }

        if (! filled(trim($this->name_ur))) {
            $missing[] = __('agricart.brands.fields.name_ur');
        }

        if (! filled($this->storedImagePath)) {
            $missing[] = __('agricart.brands.fields.image');
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.brands.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(BrandService::class);
            $payload = $this->buildPayload($service);

            if ($this->brandId) {
                $brand = Brand::query()->findOrFail($this->brandId);
                $service->update($brand, $payload);
            } else {
                $brand = $service->create($payload);
                $this->brandId = $brand->id;
                $this->code = $brand->code;
                $this->mode = 'edit';
            }

            $this->storedImagePath = $payload['image_path'] ?? $this->storedImagePath;
            $this->imagePreviewUrl = filled($this->storedImagePath)
                ? Storage::disk('public')->url($this->storedImagePath)
                : null;
            $this->setStatus(__('agricart.brands.messages.saved'), 'success');

            return true;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.brands.messages.validation_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('brand-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(BrandService $service): array
    {
        if (! filled($this->slug) && filled($this->name_en)) {
            $this->slug = $service->uniqueSlug($service->slugFromName($this->name_en), $this->brandId);
        }

        return [
            'code' => $this->code ?: null,
            'slug' => $this->slug,
            'status' => $this->status ?: 'active',
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'image_path' => $this->storedImagePath,
            'description_en' => $this->description_en ?: null,
            'description_ur' => $this->description_ur ?: null,
            'website_url' => filled($this->website_url) ? $this->website_url : null,
            'ai_status' => $this->ai_status ?: 'none',
        ];
    }

    private function persistUploadedImage(): void
    {
        if ($this->imageUpload === null) {
            return;
        }

        $path = $this->imageUpload->store('brands', 'public');
        $this->storedImagePath = $path;
        $this->imagePreviewUrl = Storage::disk('public')->url($path);
        $this->imageUpload = null;
    }

    private function resolveImagePathForAi(): ?string
    {
        if ($this->imageUpload !== null) {
            return $this->imageUpload->getRealPath() ?: null;
        }

        if (filled($this->storedImagePath)) {
            $path = Storage::disk('public')->path($this->storedImagePath);

            return is_readable($path) ? $path : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['name_ur', 'description_en', 'description_ur', 'slug'] as $field) {
            if (filled($data[$field] ?? null)) {
                $this->{$field} = (string) $data[$field];
            }
        }

        if (filled($data['ai_status'] ?? null)) {
            $this->ai_status = (string) $data['ai_status'];
        }
    }

    private function loadBrand(int $brandId): void
    {
        $brand = Brand::query()->findOrFail($brandId);

        $this->name_en = $brand->name_en;
        $this->name_ur = $brand->name_ur ?? '';
        $this->description_en = $brand->description_en ?? '';
        $this->description_ur = $brand->description_ur ?? '';
        $this->website_url = $brand->website_url ?? '';
        $this->code = $brand->code;
        $this->slug = $brand->slug;
        $this->status = $brand->status;
        $this->ai_status = $brand->ai_status?->value ?? 'none';
        $this->storedImagePath = $brand->image_path;
        $this->imagePreviewUrl = filled($brand->image_path)
            ? Storage::disk('public')->url($brand->image_path)
            : null;
        $this->enrichmentPrompt = BrandEnrichmentService::defaultPrompt();
    }

    private function resetForm(): void
    {
        $this->brandId = null;
        $this->mode = 'create';
        $this->imageUpload = null;
        $this->imagePreviewUrl = null;
        $this->storedImagePath = null;
        $this->enrichmentPrompt = BrandEnrichmentService::defaultPrompt();
        $this->name_en = '';
        $this->name_ur = '';
        $this->description_en = '';
        $this->description_ur = '';
        $this->website_url = '';
        $this->code = app(BrandService::class)->nextCode();
        $this->slug = '';
        $this->status = 'active';
        $this->ai_status = 'none';
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
