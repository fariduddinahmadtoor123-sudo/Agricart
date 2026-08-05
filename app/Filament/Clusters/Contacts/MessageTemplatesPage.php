<?php

namespace App\Filament\Clusters\Contacts;

use App\Filament\Clusters\ContactsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MessageTemplatesPage extends BaseModulePage
{
    protected static ?string $cluster = ContactsCluster::class;

    protected static ?string $navigationLabel = 'Message Templates';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'message-templates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected string $view = 'filament.pages.contacts.message-templates';

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
        return ContactsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
