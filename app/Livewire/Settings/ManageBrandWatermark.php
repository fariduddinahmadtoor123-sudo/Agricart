<?php

namespace App\Livewire\Settings;

use App\Models\StoreSetting;
use App\Models\User;
use App\Services\Catalog\BrandImageWatermarkService;
use App\Services\Media\OptimizedImageStorage;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ManageBrandWatermark extends Component
{
    use WithFileUploads;

    public bool $enabled = true;

    public $logoUpload = null;

    public ?string $logoPreviewUrl = null;

    public string $text = 'Available at Agricart.pk';

    public string $position = 'bottom-right';

    public int $sizePercent = 18;

    public int $opacity = 70;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public bool $saving = false;

    public bool $applying = false;

    public function mount(BrandImageWatermarkService $watermarks): void
    {
        $this->hydrateFromSettings($watermarks);
    }

    public function updatedLogoUpload(BrandImageWatermarkService $watermarks): void
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
        $settings->update(['brand_watermark_logo_path' => $path]);
        $watermarks->bumpWatermarkVersion($settings->fresh());
        $this->logoUpload = null;
        $this->hydrateFromSettings($watermarks);
        $this->statusMessage = __('agricart.brand_watermark.messages.logo_saved');
        $this->statusType = 'success';
    }

    public function removeLogo(BrandImageWatermarkService $watermarks): void
    {
        if (! $this->canManage()) {
            return;
        }

        $settings = StoreSetting::current();
        $settings->update(['brand_watermark_logo_path' => null]);
        $watermarks->bumpWatermarkVersion($settings->fresh());
        $this->hydrateFromSettings($watermarks);
        $this->statusMessage = __('agricart.brand_watermark.messages.logo_removed');
        $this->statusType = 'success';
    }

    public function save(BrandImageWatermarkService $watermarks): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->saving = true;
        $this->statusMessage = null;

        try {
            $positions = config('media.brand_watermark.positions', ['bottom-right']);

            $this->validate([
                'enabled' => ['boolean'],
                'text' => ['nullable', 'string', 'max:120'],
                'position' => ['required', Rule::in($positions)],
                'sizePercent' => ['required', 'integer', 'min:5', 'max:40'],
                'opacity' => ['required', 'integer', 'min:10', 'max:100'],
            ]);

            $settings = StoreSetting::current();
            $settings->update([
                'brand_watermark_enabled' => $this->enabled,
                'brand_watermark_text' => trim($this->text),
                'brand_watermark_position' => $this->position,
                'brand_watermark_size_percent' => $this->sizePercent,
                'brand_watermark_opacity' => $this->opacity,
            ]);
            $watermarks->bumpWatermarkVersion($settings->fresh());
            $this->hydrateFromSettings($watermarks);
            $this->statusMessage = __('agricart.brand_watermark.messages.saved');
            $this->statusType = 'success';
        } catch (ValidationException $exception) {
            $this->statusMessage = collect($exception->errors())->flatten()->first()
                ?: __('agricart.brand_watermark.messages.validation_failed');
            $this->statusType = 'danger';
        } catch (Throwable $exception) {
            $this->statusMessage = FriendlyError::message($exception);
            $this->statusType = 'danger';
        } finally {
            $this->saving = false;
        }
    }

    public function clearText(BrandImageWatermarkService $watermarks): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->text = '';
        $settings = StoreSetting::current();
        $settings->update(['brand_watermark_text' => '']);
        $watermarks->bumpWatermarkVersion($settings->fresh());
        $this->hydrateFromSettings($watermarks);
        $this->statusMessage = __('agricart.brand_watermark.messages.text_cleared');
        $this->statusType = 'success';
    }

    public function applyToAllBrands(BrandImageWatermarkService $watermarks): void
    {
        $this->runApply($watermarks, false);
    }

    public function forceApplyToAllBrands(BrandImageWatermarkService $watermarks): void
    {
        $this->runApply($watermarks, true);
    }

    public function render(): View
    {
        return view('livewire.settings.manage-brand-watermark', [
            'canManage' => $this->canManage(),
            'positions' => config('media.brand_watermark.positions', []),
            'version' => (int) (StoreSetting::current()->brand_watermark_version ?: 1),
        ]);
    }

    private function runApply(BrandImageWatermarkService $watermarks, bool $force): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->applying = true;
        $this->statusMessage = null;

        try {
            $stats = $watermarks->applyToAll($force);
            $this->statusMessage = __('agricart.brand_watermark.messages.applied', [
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

    private function hydrateFromSettings(BrandImageWatermarkService $watermarks): void
    {
        unset($watermarks);
        $settings = StoreSetting::current();
        $this->enabled = (bool) ($settings->brand_watermark_enabled ?? true);
        $this->text = (string) ($settings->brand_watermark_text ?? '');
        $this->position = (string) ($settings->brand_watermark_position ?: 'bottom-right');
        $this->sizePercent = (int) ($settings->brand_watermark_size_percent ?: 18);
        $this->opacity = (int) ($settings->brand_watermark_opacity ?: 70);
        $this->logoPreviewUrl = filled($settings->brand_watermark_logo_path)
            ? Storage::disk('public')->url($settings->brand_watermark_logo_path)
            : null;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canUseAdmin();
    }
}
