<?php

namespace App\Services\Store;

use App\Models\CompanyProfile;
use App\Models\StoreAboutSetting;
use App\Models\StorePage;
use App\Models\StoreSetting;
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StoreAboutService
{
    public function __construct(
        private readonly OptimizedImageStorage $images,
    ) {}

    public function settings(): StoreAboutSetting
    {
        return StoreAboutSetting::current();
    }

    /**
     * Prefer Store → About story; fall back to CMS page slug "about-us"
     * so footer/CMS edits still appear on /about.
     */
    public function resolvedStory(?StoreAboutSetting $about = null): ?string
    {
        $about ??= $this->settings();
        $story = $about->localized('story');

        if (filled($story)) {
            return $story;
        }

        $page = StorePage::query()
            ->where('slug', 'about-us')
            ->where('is_active', true)
            ->first();

        return $page?->localizedContent();
    }

    /**
     * Keep dedicated About settings in sync when the legacy CMS about-us page is saved.
     */
    public function syncStoryFromCmsPage(StorePage $page): void
    {
        if ($page->slug !== 'about-us') {
            return;
        }

        $about = $this->settings();
        $about->forceFill([
            'story_en' => filled($page->content_en) ? $page->content_en : null,
            'story_ur' => filled($page->content_ur) ? $page->content_ur : null,
        ])->save();
    }

    /**
     * @return array{
     *     about: StoreAboutSetting,
     *     company: CompanyProfile,
     *     siteName: string,
     *     tagline: ?string,
     *     logoUrl: ?string,
     *     story: ?string,
     *     heroYears: ?string,
     *     ceoName: ?string,
     *     ceoTitle: ?string,
     *     ceoBio: ?string,
     *     ceoPhotoUrl: ?string,
     *     officeGallery: list<array<string, mixed>>,
     *     teamRoster: list<array<string, mixed>>,
     *     hasCeo: bool,
     *     hasOffice: bool,
     *     hasTeam: bool
     * }
     */
    public function publicPageData(): array
    {
        $about = $this->settings();
        $company = CompanyProfile::current();
        $store = StoreSetting::current();
        $siteName = $store->localized('site_name')
            ?? $company->localized('business_name')
            ?? config('app.name');

        $office = $about->officeGallery();
        $team = $about->teamRoster();

        return [
            'about' => $about,
            'company' => $company,
            'siteName' => $siteName,
            'tagline' => $company->localized('tagline'),
            'logoUrl' => $company->displayLogoUrl() ?? (
                filled($store->logo_path) ? Storage::disk('public')->url($store->logo_path) : null
            ),
            'story' => $this->resolvedStory($about),
            'heroYears' => filled($about->hero_years_label) ? $about->hero_years_label : null,
            'ceoName' => filled($about->ceo_name) ? $about->ceo_name : null,
            'ceoTitle' => $about->localized('ceo_title'),
            'ceoBio' => $about->localized('ceo_bio'),
            'ceoPhotoUrl' => $about->ceoPhotoUrl(),
            'officeGallery' => $office,
            'teamRoster' => $team,
            'hasCeo' => $about->hasCeoSection(),
            'hasOffice' => $office !== [],
            'hasTeam' => $team !== [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveText(array $data): StoreAboutSetting
    {
        $validated = Validator::make($data, [
            'story_en' => ['nullable', 'string', 'max:20000'],
            'story_ur' => ['nullable', 'string', 'max:20000'],
            'hero_years_label' => ['nullable', 'string', 'max:120'],
            'ceo_name' => ['nullable', 'string', 'max:120'],
            'ceo_title_en' => ['nullable', 'string', 'max:120'],
            'ceo_title_ur' => ['nullable', 'string', 'max:120'],
            'ceo_bio_en' => ['nullable', 'string', 'max:4000'],
            'ceo_bio_ur' => ['nullable', 'string', 'max:4000'],
            'office_images' => ['nullable', 'array', 'max:8'],
            'office_images.*.path' => ['required_with:office_images', 'string', 'max:500'],
            'office_images.*.caption_en' => ['nullable', 'string', 'max:160'],
            'office_images.*.caption_ur' => ['nullable', 'string', 'max:160'],
            'office_images.*.sort' => ['nullable', 'integer', 'min:0', 'max:99'],
            'team_members' => ['nullable', 'array', 'max:24'],
            'team_members.*.name' => ['nullable', 'string', 'max:120'],
            'team_members.*.role_en' => ['nullable', 'string', 'max:120'],
            'team_members.*.role_ur' => ['nullable', 'string', 'max:120'],
            'team_members.*.photo_path' => ['nullable', 'string', 'max:500'],
            'team_members.*.sort' => ['nullable', 'integer', 'min:0', 'max:99'],
        ])->validate();

        $teamMembers = array_values(array_filter(
            $validated['team_members'] ?? [],
            static fn (array $row): bool => filled($row['name'] ?? null)
                || filled($row['role_en'] ?? null)
                || filled($row['role_ur'] ?? null)
                || filled($row['photo_path'] ?? null),
        ));

        $about = $this->settings();
        $about->fill([
            'story_en' => $this->nullableTrim($validated['story_en'] ?? null),
            'story_ur' => $this->nullableTrim($validated['story_ur'] ?? null),
            'hero_years_label' => $this->nullableTrim($validated['hero_years_label'] ?? null),
            'ceo_name' => $this->nullableTrim($validated['ceo_name'] ?? null),
            'ceo_title_en' => $this->nullableTrim($validated['ceo_title_en'] ?? null),
            'ceo_title_ur' => $this->nullableTrim($validated['ceo_title_ur'] ?? null),
            'ceo_bio_en' => $this->nullableTrim($validated['ceo_bio_en'] ?? null),
            'ceo_bio_ur' => $this->nullableTrim($validated['ceo_bio_ur'] ?? null),
            'office_images' => array_values($validated['office_images'] ?? []),
            'team_members' => $teamMembers,
        ])->save();

        app(StoreLayoutService::class)->clearCache();

        return $about->refresh();
    }

    public function storeCeoPhoto(TemporaryUploadedFile|UploadedFile $file): string
    {
        $this->validateImage($file, 4096);
        $about = $this->settings();
        $path = $this->images->store($file, 'store/about', 'avatar');
        $old = $about->ceo_photo_path;
        $about->update(['ceo_photo_path' => $path]);
        $this->deleteIfUnused($old);
        app(StoreLayoutService::class)->clearCache();

        return $path;
    }

    public function removeCeoPhoto(): void
    {
        $about = $this->settings();
        $old = $about->ceo_photo_path;
        $about->update(['ceo_photo_path' => null]);
        $this->deleteIfUnused($old);
        app(StoreLayoutService::class)->clearCache();
    }

    public function addOfficeImage(TemporaryUploadedFile|UploadedFile $file, ?string $captionEn = null, ?string $captionUr = null): void
    {
        $this->validateImage($file, 5120);
        $about = $this->settings();
        $images = array_values($about->office_images ?? []);

        if (count($images) >= 8) {
            throw ValidationException::withMessages([
                'officeUpload' => [__('agricart.store.admin.about.office_max')],
            ]);
        }

        $path = $this->images->store($file, 'store/about', 'gallery');
        $images[] = [
            'path' => $path,
            'caption_en' => $this->nullableTrim($captionEn),
            'caption_ur' => $this->nullableTrim($captionUr),
            'sort' => count($images),
        ];
        $about->update(['office_images' => $images]);
        app(StoreLayoutService::class)->clearCache();
    }

    public function removeOfficeImage(int $index): void
    {
        $about = $this->settings();
        $images = array_values($about->office_images ?? []);
        if (! array_key_exists($index, $images)) {
            return;
        }

        $old = $images[$index]['path'] ?? null;
        unset($images[$index]);
        $images = array_values(array_map(function (array $item, int $i): array {
            $item['sort'] = $i;

            return $item;
        }, $images, array_keys($images)));

        $about->update(['office_images' => $images]);
        $this->deleteIfUnused(is_string($old) ? $old : null);
        app(StoreLayoutService::class)->clearCache();
    }

    public function storeTeamPhoto(TemporaryUploadedFile|UploadedFile $file, int $index): void
    {
        $this->validateImage($file, 4096);
        $about = $this->settings();
        $members = array_values($about->team_members ?? []);
        if (! array_key_exists($index, $members)) {
            throw ValidationException::withMessages([
                'teamPhotoUpload' => [__('agricart.store.admin.about.team_missing')],
            ]);
        }

        $path = $this->images->store($file, 'store/about', 'avatar');
        $old = $members[$index]['photo_path'] ?? null;
        $members[$index]['photo_path'] = $path;
        $about->update(['team_members' => $members]);
        $this->deleteIfUnused(is_string($old) ? $old : null);
        app(StoreLayoutService::class)->clearCache();
    }

    private function validateImage(TemporaryUploadedFile|UploadedFile $file, int $maxKb): void
    {
        Validator::make(
            ['file' => $file],
            ['file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.$maxKb]],
        )->validate();
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function deleteIfUnused(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
