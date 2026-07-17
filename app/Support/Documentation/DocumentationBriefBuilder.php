<?php

namespace App\Support\Documentation;

use Illuminate\Support\Arr;

class DocumentationBriefBuilder
{
    /**
     * Turn the verified detailed guide into a concise handoff brief.
     *
     * @param  array<string, mixed>  $guide
     * @return array<string, mixed>
     */
    public static function fromGuide(
        string $moduleKey,
        string $pageKey,
        array $guide,
        string $locale,
    ): array {
        $sections = collect($guide['sections'] ?? []);
        $fields = [];
        $article = [];
        $improvements = [];
        $technical = [];
        $uiFlow = [];
        $validation = [];
        $database = [];
        $dependencies = [];
        $edgeCases = [];
        $acceptance = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $type = (string) ($section['type'] ?? '');
            $heading = (string) ($section['heading'] ?? '');

            foreach (Arr::wrap($section['paragraphs'] ?? []) as $paragraph) {
                if (is_string($paragraph) && $paragraph !== '') {
                    $article[] = $paragraph;
                }
            }

            foreach (($section['fields'] ?? []) as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $noteParts = array_filter([
                    ! empty($field['type']) ? static::stringify($field['type']) : null,
                    array_key_exists('required', $field)
                        ? ((bool) $field['required']
                            ? static::text($locale, 'لازمی', 'Required')
                            : static::text($locale, 'اختیاری', 'Optional'))
                        : null,
                    ! empty($field['rules']) ? static::stringify($field['rules']) : null,
                ]);

                $fields[] = [
                    'name' => static::stringify($field['name'] ?? ''),
                    'label' => static::stringify($field['label'] ?? ($field['name'] ?? '')),
                    'note' => implode(' — ', $noteParts),
                ];
            }

            $steps = array_values(array_filter(Arr::wrap($section['steps'] ?? []), 'is_string'));
            $list = array_values(array_filter(Arr::wrap($section['list'] ?? []), 'is_string'));

            switch ($type) {
                case 'workflows':
                    foreach ($steps as $step) {
                        $uiFlow[] = $step;
                    }

                    break;

                case 'rules':
                    foreach ($list as $item) {
                        $validation[] = $item;
                    }

                    break;

                case 'integrations':
                    foreach ($list as $item) {
                        $dependencies[] = $item;
                    }

                    break;

                case 'rebuild_notes':
                    foreach ([...$steps, ...$list] as $item) {
                        $improvements[] = ['text' => $item, 'suggest' => 'add'];
                    }

                    break;

                case 'acceptance':
                    foreach (array_values(array_filter(Arr::wrap($section['acceptance'] ?? []), 'is_string')) as $item) {
                        $acceptance[] = $item;
                    }

                    break;

                case 'technical':
                    $technical = [
                        ...$technical,
                        ...static::flattenTechnical($section['classes'] ?? []),
                    ];
                    $database = [
                        ...$database,
                        ...static::databaseHints($section['classes'] ?? [], $locale),
                    ];

                    break;

                default:
                    $label = $heading !== ''
                        ? $heading
                        : static::text($locale, 'عمل', 'Behaviour');

                    foreach ([...$steps, ...$list] as $item) {
                        $article[] = $label.': '.$item;
                    }

                    break;
            }
        }

        $meta = is_array($guide['meta'] ?? null) ? $guide['meta'] : [];
        $permissions = [];

        foreach (Arr::wrap($meta['routes'] ?? []) as $route) {
            if (is_string($route)) {
                $technical[] = static::text($locale, 'Route: ', 'Route: ').$route;
            }
        }

        foreach (Arr::wrap($meta['permissions'] ?? []) as $permission) {
            if (is_string($permission)) {
                $permissions[] = $permission;
                $technical[] = static::text($locale, 'اجازت: ', 'Permission: ').$permission;
            }
        }

        foreach (Arr::wrap($meta['related'] ?? []) as $related) {
            if (is_string($related)) {
                $dependencies[] = static::text($locale, 'متعلقہ ماڈیول: ', 'Related module: ').$related;
            }
        }

        if ($article === []) {
            $article[] = (string) ($guide['summary'] ?? static::text(
                $locale,
                'یہ صفحہ اس ماڈیول کے متعلقہ کام اور ریکارڈ سنبھالتا ہے۔',
                'This page handles the related module workflow and records.',
            ));
        }

        $prompt = static::buildPrompt(
            moduleKey: $moduleKey,
            pageKey: $pageKey,
            title: (string) ($guide['title'] ?? DocumentationRegistry::pageLabel($moduleKey, $pageKey)),
            summary: (string) ($guide['summary'] ?? ''),
            fields: $fields,
            uiFlow: $uiFlow,
            validation: $validation,
            dependencies: $dependencies,
            acceptance: $acceptance,
            improvements: $improvements,
            technical: $technical,
            locale: $locale,
        );

        return [
            'title' => (string) ($guide['title'] ?? DocumentationRegistry::pageLabel($moduleKey, $pageKey)),
            'summary' => (string) ($guide['summary'] ?? ''),
            'article' => array_values(array_unique($article)),
            'ui_flow' => array_values(array_unique($uiFlow)),
            'functional_requirements' => [],
            'fields' => $fields,
            'validation' => array_values(array_unique($validation)),
            'database' => array_values(array_unique($database)),
            'api' => [],
            'permissions' => array_values(array_unique($permissions)),
            'edge_cases' => $edgeCases,
            'business_rules' => static::placeholders($locale),
            'non_functional_requirements' => static::placeholders($locale),
            'dependencies' => array_values(array_unique($dependencies)),
            'technical' => array_values(array_unique($technical)),
            'improvements' => $improvements,
            'prompt' => $prompt,
            'generated_from_guide' => true,
        ];
    }

    /**
     * Five empty bullet placeholders for sections the team fills manually.
     *
     * @return list<string>
     */
    protected static function placeholders(string $locale): array
    {
        return array_fill(0, 5, static::text($locale, '— بعد میں بھریں —', '— To be filled later —'));
    }

    /**
     * Derive Database hints from a guide technical block (model/table names only — no invention).
     *
     * @return list<string>
     */
    protected static function databaseHints(mixed $classes, string $locale): array
    {
        if (! is_array($classes)) {
            return [];
        }

        $hints = [];

        foreach (['model' => 'Model', 'models' => 'Models', 'table' => 'Table', 'tables' => 'Tables'] as $key => $label) {
            if (! empty($classes[$key])) {
                $hints[] = $label.': '.static::stringify($classes[$key]);
            }
        }

        return $hints;
    }

    /**
     * @return list<string>
     */
    protected static function flattenTechnical(mixed $value, string $prefix = ''): array
    {
        if (is_string($value)) {
            return [$prefix !== '' ? $prefix.': '.$value : $value];
        }

        if (! is_array($value)) {
            return [];
        }

        $lines = [];

        foreach ($value as $key => $item) {
            $label = is_string($key)
                ? ($prefix !== '' ? $prefix.' / '.$key : $key)
                : $prefix;
            $lines = [...$lines, ...static::flattenTechnical($item, $label)];
        }

        return $lines;
    }

    /**
     * @param  list<array{name: string, label: string, note: string}>  $fields
     * @param  list<string>  $uiFlow
     * @param  list<string>  $validation
     * @param  list<string>  $dependencies
     * @param  list<string>  $acceptance
     * @param  list<array{text: string, suggest: string}>  $improvements
     * @param  list<string>  $technical
     */
    protected static function buildPrompt(
        string $moduleKey,
        string $pageKey,
        string $title,
        string $summary,
        array $fields,
        array $uiFlow,
        array $validation,
        array $dependencies,
        array $acceptance,
        array $improvements,
        array $technical,
        string $locale,
    ): string {
        $isUrdu = $locale === 'ur';
        $lines = [
            $isUrdu
                ? "نئے سسٹم میں “{$title}” صفحہ بالکل موجودہ لائیو سسٹم جیسا بنائیں۔"
                : "Build the “{$title}” page to match the CURRENT live system.",
            '',
            $isUrdu ? "ماڈیول: {$moduleKey}" : "Module: {$moduleKey}",
            $isUrdu ? "صفحہ key: {$pageKey}" : "Page key: {$pageKey}",
        ];

        if ($summary !== '') {
            $lines[] = ($isUrdu ? 'مقصد: ' : 'Purpose: ').$summary;
        }

        $lines = [...$lines, ...static::promptBlock($isUrdu ? 'UI Flow:' : 'UI Flow:', $uiFlow)];

        if ($fields !== []) {
            $lines[] = '';
            $lines[] = $isUrdu ? 'فیلڈز/کنٹرولز:' : 'Fields and controls:';

            foreach ($fields as $field) {
                $line = '- '.$field['label'];
                $line .= $field['name'] !== '' ? " ({$field['name']})" : '';
                $line .= $field['note'] !== '' ? " — {$field['note']}" : '';
                $lines[] = $line;
            }
        }

        $lines = [...$lines, ...static::promptBlock($isUrdu ? 'Validation / قواعد:' : 'Validation / rules:', $validation)];
        $lines = [...$lines, ...static::promptBlock($isUrdu ? 'Dependencies / انضمام:' : 'Dependencies / integrations:', $dependencies)];
        $lines = [...$lines, ...static::promptBlock($isUrdu ? 'تکنیکی حوالہ:' : 'Technical reference:', $technical)];

        if ($improvements !== []) {
            $lines[] = '';
            $lines[] = $isUrdu
                ? 'نئے سسٹم کی بہتریاں (لاگو کرنے سے پہلے مجھ سے حتمی فیصلہ لیں):'
                : 'Rebuild improvements (confirm final choices with me before implementation):';

            foreach ($improvements as $item) {
                $lines[] = '- '.$item['text'];
            }
        }

        $lines = [...$lines, ...static::promptBlock($isUrdu ? 'قبولیت (Acceptance):' : 'Acceptance criteria:', $acceptance)];

        $lines[] = '';
        $lines[] = $isUrdu
            ? 'Agricart کا منظور شدہ sharp/square UI، permissions، validation، transactions، audit trail اور automated tests برقرار رکھیں۔ پہلے موجودہ رویے اور تجویز کردہ بہتری کو الگ لکھ کر implementation plan دیں؛ غیر واضح business rule خود سے فرض نہ کریں۔'
            : 'Keep Agricart’s approved sharp/square UI, permissions, validation, transactions, audit trail, and automated tests. First separate existing behaviour from proposed improvements and present an implementation plan; do not invent unclear business rules.';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    protected static function promptBlock(string $heading, array $items): array
    {
        if ($items === []) {
            return [];
        }

        $lines = ['', $heading];

        foreach ($items as $item) {
            $lines[] = '- '.$item;
        }

        return $lines;
    }

    protected static function text(string $locale, string $urdu, string $english): string
    {
        return $locale === 'ur' ? $urdu : $english;
    }

    protected static function stringify(mixed $value): string
    {
        if (is_array($value)) {
            return implode('; ', array_map(
                fn (mixed $item): string => static::stringify($item),
                $value,
            ));
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
