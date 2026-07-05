<?php

namespace App\Services\Store;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StoreCategoryService
{
    /**
     * @return Collection<int, Category>
     */
    public function rootCategories(): Collection
    {
        return $this->activeChildrenOf(null);
    }

    /**
     * @return Collection<int, Category>
     */
    public function childCategories(Category $parent): Collection
    {
        return $this->activeChildrenOf($parent->id);
    }

    /**
     * @return Collection<int, Category>
     */
    private function activeChildrenOf(?int $parentId): Collection
    {
        return Category::query()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            )
            ->where('status', 'active')
            ->orderBy('name_en')
            ->get();
    }

    public function imageUrl(?Category $category): ?string
    {
        if ($category === null || ! filled($category->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($category->image_path);
    }

    public function localizedName(Category $category): string
    {
        if (app()->getLocale() === 'ur' && filled($category->name_ur)) {
            return $category->name_ur;
        }

        return $category->name_en;
    }

    public function localizedField(Category $category, string $base): ?string
    {
        $localizedKey = $base . '_' . app()->getLocale();

        if (app()->getLocale() !== 'en' && filled($category->{$localizedKey})) {
            return $category->{$localizedKey};
        }

        $englishKey = $base . '_en';
        $value = $category->{$englishKey};

        return filled($value) ? $value : null;
    }

    /**
     * @return list<array{key: string, label: string, content: string, variant: string}>
     */
    public function storefrontContentBlocks(Category $category): array
    {
        $fields = [
            'short_description' => __('agricart.categories.fields.short_description'),
            'full_description' => __('agricart.categories.fields.full_description'),
            'usage_guidelines' => __('agricart.categories.fields.usage_guidelines'),
            'benefits' => __('agricart.categories.fields.benefits'),
            'warnings' => __('agricart.categories.fields.warnings'),
            'buying_guide' => __('agricart.categories.fields.buying_guide'),
            'customs_notes' => __('agricart.categories.fields.customs_notes'),
            'import_notes' => __('agricart.categories.fields.import_notes'),
            'export_notes' => __('agricart.categories.fields.export_notes'),
        ];

        $blocks = [];

        foreach ($fields as $key => $label) {
            $content = trim((string) ($this->localizedField($category, $key) ?? ''));

            if ($content === '') {
                continue;
            }

            $blocks[] = [
                'key' => $key,
                'label' => $label,
                'content' => $content,
                'variant' => $key === 'warnings' ? 'warning' : 'default',
            ];
        }

        return $blocks;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function localizedFaqs(Category $category): array
    {
        $faqs = $category->faqs;

        if (! is_array($faqs)) {
            return [];
        }

        $items = [];

        foreach ($faqs as $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = trim((string) (
                app()->getLocale() === 'ur' && filled($faq['question_ur'] ?? null)
                    ? $faq['question_ur']
                    : ($faq['question_en'] ?? '')
            ));

            $answer = trim((string) (
                app()->getLocale() === 'ur' && filled($faq['answer_ur'] ?? null)
                    ? $faq['answer_ur']
                    : ($faq['answer_en'] ?? '')
            ));

            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }

    public function hasStorefrontContent(Category $category): bool
    {
        return $this->storefrontContentBlocks($category) !== []
            || $this->localizedFaqs($category) !== []
            || filled($category->hs_code);
    }
}
