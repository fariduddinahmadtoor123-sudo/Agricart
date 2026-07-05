<?php

namespace App\Filament\Forms;

use App\Enums\CategoryAiStatus;
use App\Models\Category;

class CategoryForm
{
    /**
     * Fields that existed in the UI/schema before this e-commerce standard update.
     *
     * @return list<string>
     */
    public static function preExistingFieldKeys(): array
    {
        return [
            'name_en',
            'name_ur',
            'code',
            'status',
            'visual_mapping',
        ];
    }

    /**
     * @return list<string>
     */
    public static function detailSectionKeys(): array
    {
        return ['core', 'content', 'seo', 'compliance', 'faqs', 'system'];
    }

    /**
     * Step scroll anchors for the category form footer navigation.
     *
     * @return array<int, string>
     */
    public static function stepScrollAnchors(): array
    {
        return [
            1 => 'hero',
            2 => 'core',
            3 => 'seo',
        ];
    }

    /**
     * @return array<int, list<string>>
     */
    public static function stepSectionKeys(): array
    {
        return [
            1 => ['discovery'],
            2 => ['core', 'content'],
            3 => ['seo', 'compliance', 'faqs', 'system'],
        ];
    }

    /**
     * @return list<array{key: string, label: string, fields: list<array<string, mixed>>}>
     */
    public static function sections(): array
    {
        return [
            [
                'key' => 'discovery',
                'label' => __('agricart.categories.sections.discovery'),
                'fields' => [
                    [
                        'type' => 'visual_mapping',
                        'name' => 'visual_mapping',
                        'full' => true,
                    ],
                    [
                        'type' => 'category_names',
                        'base' => 'name',
                        'required' => true,
                    ],
                ],
            ],
            [
                'key' => 'core',
                'label' => __('agricart.categories.sections.core'),
                'fields' => [
                    [
                        'type' => 'parent',
                        'name' => 'parent_id',
                        'label' => __('agricart.categories.fields.parent'),
                        'full' => false,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'slug',
                        'label' => __('agricart.categories.fields.slug'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'code',
                        'label' => __('agricart.categories.fields.code'),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'status',
                        'label' => __('agricart.categories.fields.status'),
                        'options' => self::statusOptions(),
                    ],
                ],
            ],
            [
                'key' => 'content',
                'label' => __('agricart.categories.sections.content'),
                'fields' => self::bilingualTextareaFields([
                    'short_description' => __('agricart.categories.fields.short_description'),
                    'full_description' => __('agricart.categories.fields.full_description'),
                    'usage_guidelines' => __('agricart.categories.fields.usage_guidelines'),
                    'benefits' => __('agricart.categories.fields.benefits'),
                    'warnings' => __('agricart.categories.fields.warnings'),
                    'buying_guide' => __('agricart.categories.fields.buying_guide'),
                ], shortDescriptionRows: 3, fullDescriptionRows: 6),
            ],
            [
                'key' => 'seo',
                'label' => __('agricart.categories.sections.seo'),
                'fields' => array_merge(
                    self::bilingualTextFields([
                        'seo_title' => __('agricart.categories.fields.seo_title'),
                        'seo_focus_keyword' => __('agricart.categories.fields.seo_focus_keyword'),
                        'seo_keywords' => __('agricart.categories.fields.seo_keywords'),
                    ]),
                    self::bilingualTextareaFields([
                        'seo_description' => __('agricart.categories.fields.seo_description'),
                    ], shortDescriptionRows: 3),
                    [
                        [
                            'type' => 'json',
                            'name' => 'search_terms',
                            'label' => __('agricart.categories.fields.search_terms'),
                            'placeholder' => '["term one", "term two"]',
                            'full' => true,
                        ],
                    ],
                ),
            ],
            [
                'key' => 'compliance',
                'label' => __('agricart.categories.sections.compliance'),
                'fields' => array_merge(
                    [
                        [
                            'type' => 'text',
                            'name' => 'hs_code',
                            'label' => __('agricart.categories.fields.hs_code'),
                        ],
                    ],
                    self::bilingualTextareaFields([
                        'customs_notes' => __('agricart.categories.fields.customs_notes'),
                        'import_notes' => __('agricart.categories.fields.import_notes'),
                        'export_notes' => __('agricart.categories.fields.export_notes'),
                    ]),
                ),
            ],
            [
                'key' => 'faqs',
                'label' => __('agricart.categories.sections.faqs'),
                'fields' => [
                    [
                        'type' => 'faqs_repeater',
                        'name' => 'faqs',
                        'full' => true,
                    ],
                ],
            ],
            [
                'key' => 'system',
                'label' => __('agricart.categories.sections.system'),
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'ai_status',
                        'label' => __('agricart.categories.fields.ai_status'),
                        'options' => self::aiStatusOptions(),
                        'full' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            'active' => __('agricart.form_panel.statuses.active'),
            'inactive' => __('agricart.form_panel.statuses.inactive'),
            'draft' => __('agricart.form_panel.statuses.draft'),
        ];
    }

    /**
     * @return list<string, string>
     */
    public static function aiStatusOptions(): array
    {
        $options = [];

        foreach (CategoryAiStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function allDatabaseColumns(): array
    {
        return [
            'parent_id', 'slug', 'code', 'status', 'name_en', 'name_ur',
            'short_description_en', 'short_description_ur',
            'full_description_en', 'full_description_ur',
            'usage_guidelines_en', 'usage_guidelines_ur',
            'benefits_en', 'benefits_ur',
            'warnings_en', 'warnings_ur',
            'buying_guide_en', 'buying_guide_ur',
            'seo_title_en', 'seo_title_ur',
            'seo_description_en', 'seo_description_ur',
            'seo_keywords_en', 'seo_keywords_ur',
            'seo_focus_keyword_en', 'seo_focus_keyword_ur',
            'search_terms', 'hs_code',
            'customs_notes_en', 'customs_notes_ur',
            'import_notes_en', 'import_notes_ur',
            'export_notes_en', 'export_notes_ur',
            'visual_mapping', 'faqs', 'ai_status',
        ];
    }

    /**
     * @return list<array{base: string, en: string, ur: string}>
     */
    public static function bilingualPairs(): array
    {
        return [
            ['base' => 'name', 'en' => 'name_en', 'ur' => 'name_ur'],
            ['base' => 'short_description', 'en' => 'short_description_en', 'ur' => 'short_description_ur'],
            ['base' => 'full_description', 'en' => 'full_description_en', 'ur' => 'full_description_ur'],
            ['base' => 'usage_guidelines', 'en' => 'usage_guidelines_en', 'ur' => 'usage_guidelines_ur'],
            ['base' => 'benefits', 'en' => 'benefits_en', 'ur' => 'benefits_ur'],
            ['base' => 'warnings', 'en' => 'warnings_en', 'ur' => 'warnings_ur'],
            ['base' => 'buying_guide', 'en' => 'buying_guide_en', 'ur' => 'buying_guide_ur'],
            ['base' => 'seo_title', 'en' => 'seo_title_en', 'ur' => 'seo_title_ur'],
            ['base' => 'seo_description', 'en' => 'seo_description_en', 'ur' => 'seo_description_ur'],
            ['base' => 'seo_keywords', 'en' => 'seo_keywords_en', 'ur' => 'seo_keywords_ur'],
            ['base' => 'seo_focus_keyword', 'en' => 'seo_focus_keyword_en', 'ur' => 'seo_focus_keyword_ur'],
            ['base' => 'customs_notes', 'en' => 'customs_notes_en', 'ur' => 'customs_notes_ur'],
            ['base' => 'import_notes', 'en' => 'import_notes_en', 'ur' => 'import_notes_ur'],
            ['base' => 'export_notes', 'en' => 'export_notes_en', 'ur' => 'export_notes_ur'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function parentOptions(?int $exceptId = null): array
    {
        return static::categoryLabelOptions($exceptId);
    }

    /**
     * @return array<int|string, string>
     */
    public static function displayCategoryOptions(?int $exceptId = null): array
    {
        return static::categoryLabelOptions($exceptId);
    }

    /**
     * @return array<int, string>
     */
    public static function searchCategoryLabels(
        string $search,
        ?int $exceptId = null,
        array $excludeIds = [],
        int $limit = 20,
    ): array {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $needle = '%' . $term . '%';
        $exclude = collect($excludeIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->when($exceptId, fn ($collection) => $collection->push((int) $exceptId))
            ->unique()
            ->values()
            ->all();

        return Category::query()
            ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('slug', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit($limit)
            ->get(['id', 'name_en', 'name_ur'])
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => static::formatCategoryLabel($category),
            ])
            ->all();
    }

    public static function labelForId(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $category = Category::query()->find($id, ['id', 'name_en', 'name_ur']);

        return $category ? static::formatCategoryLabel($category) : null;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public static function categoryLabelsByIds(array $ids): array
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return Category::query()
            ->whereIn('id', $ids)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ur'])
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => static::formatCategoryLabel($category),
            ])
            ->all();
    }

    private static function formatCategoryLabel(Category $category): string
    {
        $label = $category->name_en;

        if (filled($category->name_ur)) {
            $label .= ' — ' . $category->name_ur;
        }

        return $label;
    }

    /**
     * @return array<int, string>
     */
    public static function categoryLabelOptions(?int $exceptId = null): array
    {
        return Category::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->orderBy('name_en')
            ->limit(500)
            ->get(['id', 'name_en', 'name_ur'])
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => static::formatCategoryLabel($category),
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $labels
     * @return list<array<string, mixed>>
     */
    protected static function bilingualTextareaFields(
        array $labels,
        int $shortDescriptionRows = 4,
        int $fullDescriptionRows = 4,
    ): array {
        $fields = [];

        foreach ($labels as $base => $label) {
            $rows = str_contains($base, 'full') ? $fullDescriptionRows : $shortDescriptionRows;

            $fields[] = [
                'type' => 'bilingual_textarea',
                'base' => $base,
                'label' => $label,
                'rows' => $rows,
                'full' => true,
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, string>  $labels
     * @return list<array<string, mixed>>
     */
    protected static function bilingualTextFields(array $labels): array
    {
        $fields = [];

        foreach ($labels as $base => $label) {
            $fields[] = [
                'type' => 'bilingual_text',
                'base' => $base,
                'label' => $label,
            ];
        }

        return $fields;
    }
}
