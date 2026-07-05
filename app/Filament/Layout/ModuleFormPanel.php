<?php

namespace App\Filament\Layout;

use Illuminate\Support\Str;

class ModuleFormPanel
{
    /**
     * @return list<array{key: string, label: string, color: string|null, outlined: bool, icon: string|null}>
     */
    public static function footerActions(): array
    {
        return [
            [
                'key' => 'ai',
                'label' => __('agricart.form_panel.actions.ai'),
                'color' => 'warning',
                'outlined' => false,
                'icon' => 'sparkles',
            ],
            [
                'key' => 'save_next',
                'label' => __('agricart.form_panel.actions.save_next'),
                'color' => 'gray',
                'outlined' => true,
                'icon' => 'arrow-right',
            ],
            [
                'key' => 'save_close',
                'label' => __('agricart.form_panel.actions.save_close'),
                'color' => 'primary',
                'outlined' => false,
                'icon' => 'check',
            ],
            [
                'key' => 'cancel',
                'label' => __('agricart.form_panel.actions.cancel'),
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
        return trans('agricart.form_panel.steps');
    }

    /**
     * @return list<array{name: string, label: string, type: string, required: bool, options: list<string>|null}>
     */
    public static function sampleFields(string $resourceLabel): array
    {
        return [
            [
                'name' => 'name',
                'label' => __('agricart.form_panel.fields.name', ['item' => Str::singular($resourceLabel)]),
                'type' => 'text',
                'required' => true,
                'options' => null,
            ],
            [
                'name' => 'code',
                'label' => __('agricart.form_panel.fields.code'),
                'type' => 'text',
                'required' => false,
                'options' => null,
            ],
            [
                'name' => 'status',
                'label' => __('agricart.form_panel.fields.status'),
                'type' => 'select',
                'required' => false,
                'options' => [
                    __('agricart.form_panel.statuses.active'),
                    __('agricart.form_panel.statuses.inactive'),
                    __('agricart.form_panel.statuses.draft'),
                ],
            ],
            [
                'name' => 'description',
                'label' => __('agricart.form_panel.fields.description'),
                'type' => 'textarea',
                'required' => false,
                'options' => null,
            ],
        ];
    }
}
