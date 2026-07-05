<?php

namespace App\Filament\Layout;

use Illuminate\Support\Str;

class ModuleFormPanel
{
    /**
     * Standard footer actions — same on every module form panel.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     color: string|null,
     *     outlined: bool,
     *     icon: string|null,
     * }>
     */
    public static function footerActions(): array
    {
        return [
            [
                'key' => 'ai',
                'label' => 'AI',
                'color' => 'warning',
                'outlined' => false,
                'icon' => 'sparkles',
            ],
            [
                'key' => 'save_next',
                'label' => 'Save & Add Next',
                'color' => 'gray',
                'outlined' => true,
                'icon' => 'arrow-right',
            ],
            [
                'key' => 'save_close',
                'label' => 'Save & Close',
                'color' => 'primary',
                'outlined' => false,
                'icon' => 'check',
            ],
            [
                'key' => 'cancel',
                'label' => 'Cancel',
                'color' => 'gray',
                'outlined' => true,
                'icon' => 'x-mark',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function progressSteps(): array
    {
        return [
            'Basic Information',
            'Additional Details',
            'Review & Save',
        ];
    }

    /**
     * Sample placeholder fields until module-specific forms are built.
     *
     * @return list<array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     required: bool,
     *     options: list<string>|null,
     * }>
     */
    public static function sampleFields(string $resourceLabel): array
    {
        $singular = Str::singular($resourceLabel);

        return [
            [
                'name' => 'name',
                'label' => $singular . ' Name',
                'type' => 'text',
                'required' => true,
                'options' => null,
            ],
            [
                'name' => 'code',
                'label' => 'Code / Reference',
                'type' => 'text',
                'required' => false,
                'options' => null,
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'required' => false,
                'options' => ['Active', 'Inactive', 'Draft'],
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'required' => false,
                'options' => null,
            ],
        ];
    }
}
