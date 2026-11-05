<?php

namespace App\Filament\Clusters\Docs;

use App\Filament\Clusters\DocsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\Access\ModulePageAccess;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class AppGuidePage extends BaseModulePage
{
    protected static ?string $cluster = DocsCluster::class;

    protected static ?string $navigationLabel = 'Full App Guide';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'app-guide';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.pages.docs.app-guide';

    #[Url(as: 'lang')]
    public string $lang = 'ur';

    /**
     * Anyone who can open Docs Overview can read the full-app guide.
     */
    protected static function requiredPermission(): ?string
    {
        return ModulePageAccess::key('docs', 'overview', 'view');
    }

    public function mount(): void
    {
        if (! in_array($this->lang, ['ur', 'en'], true)) {
            $this->lang = 'ur';
        }
    }

    public function setLang(string $lang): void
    {
        $this->lang = in_array($lang, ['ur', 'en'], true) ? $lang : 'ur';
    }

    public function documentHtml(): string
    {
        $path = base_path('docs/drops/agricart-general-map-'.$this->lang.'.md');

        if (! File::isFile($path)) {
            $path = base_path('docs/drops/agricart-general-map-ur.md');
        }

        $markdown = File::get($path);

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return DocsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
