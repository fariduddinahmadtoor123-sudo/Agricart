<?php

namespace App\Services\Store;

use App\Models\StoreRegistrationSetting;
use Illuminate\Support\Facades\Validator;

class StoreRegistrationService
{
    public function settings(): StoreRegistrationSetting
    {
        return StoreRegistrationSetting::current();
    }

    /**
     * @return array{
     *     settings: StoreRegistrationSetting,
     *     title: ?string,
     *     body: ?string,
     *     bodyHtml: ?string,
     *     requireAcceptance: bool,
     *     hasNotice: bool
     * }
     */
    public function publicNotice(string $audience): array
    {
        $audience = $audience === 'supplier' ? 'supplier' : 'staff';
        $settings = $this->settings();
        $title = $settings->localized("{$audience}_title");
        $body = $settings->localized("{$audience}_body");

        return [
            'settings' => $settings,
            'title' => $title,
            'body' => $body,
            'bodyHtml' => filled($body) ? $this->toSafeHtml($body) : null,
            'requireAcceptance' => $settings->requiresAcceptance($audience),
            'hasNotice' => $settings->hasAudienceNotice($audience),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): StoreRegistrationSetting
    {
        $validated = Validator::make($data, [
            'staff_title_en' => ['nullable', 'string', 'max:255'],
            'staff_title_ur' => ['nullable', 'string', 'max:255'],
            'staff_body_en' => ['nullable', 'string', 'max:20000'],
            'staff_body_ur' => ['nullable', 'string', 'max:20000'],
            'staff_require_acceptance' => ['boolean'],
            'supplier_title_en' => ['nullable', 'string', 'max:255'],
            'supplier_title_ur' => ['nullable', 'string', 'max:255'],
            'supplier_body_en' => ['nullable', 'string', 'max:20000'],
            'supplier_body_ur' => ['nullable', 'string', 'max:20000'],
            'supplier_require_acceptance' => ['boolean'],
        ])->validate();

        $settings = $this->settings();
        $settings->forceFill([
            'staff_title_en' => filled($validated['staff_title_en'] ?? null) ? $validated['staff_title_en'] : null,
            'staff_title_ur' => filled($validated['staff_title_ur'] ?? null) ? $validated['staff_title_ur'] : null,
            'staff_body_en' => filled($validated['staff_body_en'] ?? null) ? $validated['staff_body_en'] : null,
            'staff_body_ur' => filled($validated['staff_body_ur'] ?? null) ? $validated['staff_body_ur'] : null,
            'staff_require_acceptance' => (bool) ($validated['staff_require_acceptance'] ?? false),
            'supplier_title_en' => filled($validated['supplier_title_en'] ?? null) ? $validated['supplier_title_en'] : null,
            'supplier_title_ur' => filled($validated['supplier_title_ur'] ?? null) ? $validated['supplier_title_ur'] : null,
            'supplier_body_en' => filled($validated['supplier_body_en'] ?? null) ? $validated['supplier_body_en'] : null,
            'supplier_body_ur' => filled($validated['supplier_body_ur'] ?? null) ? $validated['supplier_body_ur'] : null,
            'supplier_require_acceptance' => (bool) ($validated['supplier_require_acceptance'] ?? false),
        ])->save();

        return $settings->refresh();
    }

    public function toSafeHtml(?string $body): string
    {
        if (! filled($body)) {
            return '';
        }

        return nl2br(e($body), false);
    }
}
