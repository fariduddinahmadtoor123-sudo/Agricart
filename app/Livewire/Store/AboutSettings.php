<?php

namespace App\Livewire\Store;

use App\Services\Store\StoreAboutService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class AboutSettings extends BaseStoreSettings
{
    use WithFileUploads;

    public string $story_en = '';

    public string $story_ur = '';

    public string $hero_years_label = '';

    public string $ceo_name = '';

    public string $ceo_title_en = '';

    public string $ceo_title_ur = '';

    public string $ceo_bio_en = '';

    public string $ceo_bio_ur = '';

    public ?string $ceoPhotoPreviewUrl = null;

    public $ceoPhotoUpload = null;

    public $officeUpload = null;

    /** @var list<array{path: string, url: string, caption_en: string, caption_ur: string, sort: int}> */
    public array $officeRows = [];

    /** @var list<array{name: string, role_en: string, role_ur: string, photo_path: ?string, photo_url: ?string, sort: int}> */
    public array $teamRows = [];

    /** @var array<int, mixed> */
    public array $teamPhotoUploads = [];

    public function mount(StoreAboutService $about): void
    {
        if (! $this->canManageStoreSettings()) {
            return;
        }

        $this->hydrateFromSettings($about);
    }

    public function save(StoreAboutService $about): void
    {
        $this->authorizeStoreSettings();
        $this->persistText($about);
        $this->hydrateFromSettings($about);
        $this->flashSaved();
    }

    public function updatedCeoPhotoUpload(StoreAboutService $about): void
    {
        $this->authorizeStoreSettings();

        if ($this->ceoPhotoUpload === null) {
            return;
        }

        $path = $about->storeCeoPhoto($this->ceoPhotoUpload);
        $this->ceoPhotoPreviewUrl = Storage::disk('public')->url($path);
        $this->ceoPhotoUpload = null;
        $this->flashSaved();
    }

    public function removeCeoPhoto(StoreAboutService $about): void
    {
        $this->authorizeStoreSettings();
        $about->removeCeoPhoto();
        $this->ceoPhotoPreviewUrl = null;
        $this->flashSaved();
    }

    public function updatedOfficeUpload(StoreAboutService $about): void
    {
        $this->authorizeStoreSettings();

        if ($this->officeUpload === null) {
            return;
        }

        $about->addOfficeImage($this->officeUpload);
        $this->officeUpload = null;
        $this->hydrateFromSettings($about);
        $this->flashSaved();
    }

    public function removeOfficeImage(int $index, StoreAboutService $about): void
    {
        $this->authorizeStoreSettings();
        $about->removeOfficeImage($index);
        $this->hydrateFromSettings($about);
        $this->flashSaved();
    }

    public function addTeamMember(): void
    {
        $this->authorizeStoreSettings();
        $this->teamRows[] = [
            'name' => '',
            'role_en' => '',
            'role_ur' => '',
            'photo_path' => null,
            'photo_url' => null,
            'sort' => count($this->teamRows),
        ];
    }

    public function removeTeamMember(int $index, StoreAboutService $about): void
    {
        $this->authorizeStoreSettings();

        if (! array_key_exists($index, $this->teamRows)) {
            return;
        }

        unset($this->teamRows[$index]);
        $this->teamRows = array_values(array_map(static function (array $row, int $i): array {
            $row['sort'] = $i;

            return $row;
        }, $this->teamRows, array_keys($this->teamRows)));

        $this->persistText($about);
        $this->hydrateFromSettings($about);
        $this->flashSaved();
    }

    public function updatedTeamPhotoUploads(mixed $value, ?string $key = null): void
    {
        $this->authorizeStoreSettings();

        if ($key === null || $value === null) {
            return;
        }

        $about = app(StoreAboutService::class);
        $index = (int) $key;
        $this->persistText($about);

        if (! array_key_exists($index, $this->teamRows) || trim($this->teamRows[$index]['name']) === '') {
            $this->addError('teamPhotoUploads.'.$index, __('agricart.store.admin.about.team_missing'));
            $this->teamPhotoUploads[$index] = null;

            return;
        }

        $about->storeTeamPhoto($value, $index);
        unset($this->teamPhotoUploads[$index]);
        $this->hydrateFromSettings($about);
        $this->flashSaved();
    }

    public function render(): View
    {
        return view('livewire.store.about-settings');
    }

    private function persistText(StoreAboutService $about): void
    {
        $about->saveText([
            'story_en' => $this->story_en,
            'story_ur' => $this->story_ur,
            'hero_years_label' => $this->hero_years_label,
            'ceo_name' => $this->ceo_name,
            'ceo_title_en' => $this->ceo_title_en,
            'ceo_title_ur' => $this->ceo_title_ur,
            'ceo_bio_en' => $this->ceo_bio_en,
            'ceo_bio_ur' => $this->ceo_bio_ur,
            'office_images' => array_map(static fn (array $row): array => [
                'path' => $row['path'],
                'caption_en' => $row['caption_en'] !== '' ? $row['caption_en'] : null,
                'caption_ur' => $row['caption_ur'] !== '' ? $row['caption_ur'] : null,
                'sort' => (int) $row['sort'],
            ], $this->officeRows),
            'team_members' => array_map(static fn (array $row): array => [
                'name' => $row['name'],
                'role_en' => $row['role_en'] !== '' ? $row['role_en'] : null,
                'role_ur' => $row['role_ur'] !== '' ? $row['role_ur'] : null,
                'photo_path' => $row['photo_path'],
                'sort' => (int) $row['sort'],
            ], $this->teamRows),
        ]);
    }

    private function hydrateFromSettings(StoreAboutService $about): void
    {
        $settings = $about->settings();

        $this->story_en = (string) ($settings->story_en ?? '');
        $this->story_ur = (string) ($settings->story_ur ?? '');
        $this->hero_years_label = (string) ($settings->hero_years_label ?? '');
        $this->ceo_name = (string) ($settings->ceo_name ?? '');
        $this->ceo_title_en = (string) ($settings->ceo_title_en ?? '');
        $this->ceo_title_ur = (string) ($settings->ceo_title_ur ?? '');
        $this->ceo_bio_en = (string) ($settings->ceo_bio_en ?? '');
        $this->ceo_bio_ur = (string) ($settings->ceo_bio_ur ?? '');
        $this->ceoPhotoPreviewUrl = $settings->ceoPhotoUrl();

        $this->officeRows = array_map(static fn (array $item): array => [
            'path' => $item['path'],
            'url' => $item['url'],
            'caption_en' => (string) ($item['caption_en'] ?? ''),
            'caption_ur' => (string) ($item['caption_ur'] ?? ''),
            'sort' => (int) $item['sort'],
        ], $settings->officeGallery());

        $this->teamRows = array_map(static fn (array $item): array => [
            'name' => $item['name'],
            'role_en' => (string) ($item['role_en'] ?? ''),
            'role_ur' => (string) ($item['role_ur'] ?? ''),
            'photo_path' => $item['photo_path'],
            'photo_url' => $item['photo_url'],
            'sort' => (int) $item['sort'],
        ], $settings->teamRoster());
    }
}
