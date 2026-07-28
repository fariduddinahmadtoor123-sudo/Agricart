<?php

namespace App\Livewire\Settings;

use App\Models\StoreSetting;
use App\Models\User;
use App\Services\Catalog\ProductImageWatermarkService;
use App\Services\Media\OptimizedImageStorage;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ManageProductWatermark extends Component
{
    use WithFileUploads;

    public bool $enabled = true;

    public $logoUpload = null;

    public ?string $logoPreviewUrl = null;

    public string $position = 'bottom-right';

    public int $sizePercent = 15;

    public int $opacity = 55;

    /** @var array<string, string> */
    public array $marketplacePreferences = [];

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public bool $saving = false;

    public bool $applying = false;

    public function mount(ProductImageWatermarkService $watermarks): void
    {
        $this->hydrateFromSettings($watermarks);
    }

    public function updatedLogoUpload(ProductImageWatermarkService $watermarks): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->validate([
            'logoUpload' => ['required', 'image', 'max:2048'],
        ]);

        if ($this->logoUpload === null) {
            return;
        }

        $path = app(OptimizedImageStorage::class)->store($this->logoUpload, 'store/watermarks');
        $settings = StoreSetting::current();
        $settings->update(['product_watermark_logo_path' => $path]);
        $watermarks->bumpWatermarkVersion($settings->fresh());
        $this->logoUpload = null;
        $this->hydrateFromSettings($watermarks);
        $this->statusMessage = __('agricart.product_watermark.messages.logo_saved');
        $this->statusType = 'success';
    }

    public function removeLogo(ProductImageWatermarkService $watermarks): void
    {
        if (! $this->canManage()) {
            return;
        }

        $settings = StoreSetting::current();
        $settings->update(['product_watermark_logo_path' => null]);
        $watermarks->bumpWatermarkVersion($settings->fresh());
        $this->hydrateFromSettings($watermarks);
        $this->statusMessage = __('agricart.product_watermark.messages.logo_removed');
        $this->statusType = 'success';
    }

    public function save(ProductImageWatermarkService $watermarks): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->saving = true;
        $this->statusMessage = null;

        try {
            $positions = config('media.product_watermark.positions', ['bottom-right']);
            $platforms = config('media.product_watermark.marketplace_platforms', []);

            $this->validate([
                'enabled' => ['boolean'],
                'position' => ['required', Rule::in($positions)],
                'sizePercent' => ['required', 'integer', 'min:5', 'max:40'],
                'opacity' => ['required', 'integer', 'min:10', 'max:100'],
                'marketplacePreferences' => ['array'],
                'marketplacePreferences.*' => [
                    'required',
                    Rule::in([
                        ProductImageWatermarkService::SOURCE_ORIGINAL,
                        ProductImageWatermarkService::SOURCE_WATERMARKED,
                    ]),
                ],
            ]);

            $prefs = [];

            foreach ($platforms as $platform) {
                $prefs[$platform] = $this->marketplacePreferences[$platform]
                    ?? ProductImageWatermarkService::SOURCE_WATERMARKED;
            }

            $settings = StoreSetting::current();
            $settings->update([
                'product_watermark_enabled' => $this->enabled,
                'product_watermark_position' => $this->position,
                'product_watermark_size_percent' => $this->sizePercent,
                'product_watermark_opacity' => $this->opacity,
                'marketplace_image_preferences' => $prefs,
            ]);
            $watermarks->bumpWatermarkVersion($settings->fresh());
            $this->hydrateFromSettings($watermarks);
            $this->statusMessage = __('agricart.product_watermark.messages.saved');
            $this->statusType = 'success';
        } catch (ValidationException $exception) {
            $this->statusMessage = collect($exception->errors())->flatten()->first()
                ?: __('agricart.product_watermark.messages.validation_failed');
            $this->statusType = 'danger';
        } catch (Throwable $exception) {
            $this->statusMessage = FriendlyError::message($exception);
            $this->statusType = 'danger';
        } finally {
            $this->saving = false;
        }
    }

    public function applyToAllProducts(ProductImageWatermarkService $watermarks): void
    {
        $this->applyToAll($watermarks, false);
    }

    public function forceApplyToAllProducts(ProductImageWatermarkService $watermarks): void
    {
        $this->applyToAll($watermarks, true);
    }

    public function applyToAll(ProductImageWatermarkService $watermarks, bool $force = false): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->applying = true;
        $this->statusMessage = null;

        try {
            $stats = $watermarks->applyToAll($force);
            $this->statusMessage = __('agricart.product_watermark.messages.applied', [
                'processed' => $stats['processed'],
                'skipped' => $stats['skipped'],
                'failed' => $stats['failed'],
            ]);
            $this->statusType = $stats['failed'] > 0 ? 'warning' : 'success';
        } catch (Throwable $exception) {
            $this->statusMessage = FriendlyError::message($exception);
            $this->statusType = 'danger';
        } finally {
            $this->applying = false;
        }
    }

    public function render(): View
    {
        return view('livewire.settings.manage-product-watermark', [
            'canManage' => $this->canManage(),
            'positions' => config('media.product_watermark.positions', []),
            'platforms' => config('media.product_watermark.marketplace_platforms', []),
            'version' => (int) (StoreSetting::current()->product_watermark_version ?: 1),
        ]);
    }

    private function hydrateFromSettings(ProductImageWatermarkService $watermarks): void
    {
        $settings = StoreSetting::current();
        $this->enabled = (bool) ($settings->product_watermark_enabled ?? true);
        $this->position = (string) ($settings->product_watermark_position ?: 'bottom-right');
        $this->sizePercent = (int) ($settings->product_watermark_size_percent ?: 15);
        $this->opacity = (int) ($settings->product_watermark_opacity ?: 55);
        $this->marketplacePreferences = $watermarks->marketplacePreferences($settings);
        $this->logoPreviewUrl = filled($settings->product_watermark_logo_path)
            ? Storage::disk('public')->url($settings->product_watermark_logo_path)
            : null;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canUseAdmin();
    }
}
