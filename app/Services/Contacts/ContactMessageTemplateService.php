<?php

namespace App\Services\Contacts;

use App\Models\ContactMessageTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContactMessageTemplateService
{
    /**
     * @return Collection<int, ContactMessageTemplate>
     */
    public function listForCustomer(bool $activeOnly = false): Collection
    {
        $query = ContactMessageTemplate::query()
            ->forAudience(ContactMessageTemplate::AUDIENCE_CUSTOMER)
            ->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function defaultForCustomer(): ?ContactMessageTemplate
    {
        $welcome = ContactMessageTemplate::query()
            ->forAudience(ContactMessageTemplate::AUDIENCE_CUSTOMER)
            ->active()
            ->where('key', 'welcome')
            ->first();

        if ($welcome !== null) {
            return $welcome;
        }

        return ContactMessageTemplate::query()
            ->forAudience(ContactMessageTemplate::AUDIENCE_CUSTOMER)
            ->active()
            ->ordered()
            ->first();
    }

    public function findCustomerTemplate(int $id): ?ContactMessageTemplate
    {
        return ContactMessageTemplate::query()
            ->forAudience(ContactMessageTemplate::AUDIENCE_CUSTOMER)
            ->whereKey($id)
            ->first();
    }

    public function applyName(string $text, string $name): string
    {
        return str_replace(':name', $name, $text);
    }

    public function restoreNamePlaceholder(string $text, string $name): string
    {
        return str_replace($name, ':name', $text);
    }

    /**
     * @param  array{
     *     name_en: string,
     *     name_ur?: string|null,
     *     subject_en: string,
     *     subject_ur?: string|null,
     *     body_en: string,
     *     body_ur?: string|null,
     *     is_active?: bool,
     *     sort_order?: int
     * }  $payload
     */
    public function createCustomerTemplate(array $payload): ContactMessageTemplate
    {
        $normalized = $this->normalizePayload($payload);
        $this->assertValidContent($normalized);

        $maxSort = (int) ContactMessageTemplate::query()
            ->forAudience(ContactMessageTemplate::AUDIENCE_CUSTOMER)
            ->max('sort_order');

        return ContactMessageTemplate::query()->create([
            'key' => $this->uniqueKey($normalized['name_en']),
            'name_en' => $normalized['name_en'],
            'name_ur' => $normalized['name_ur'],
            'subject_en' => $normalized['subject_en'],
            'subject_ur' => $normalized['subject_ur'],
            'body_en' => $normalized['body_en'],
            'body_ur' => $normalized['body_ur'],
            'audience' => ContactMessageTemplate::AUDIENCE_CUSTOMER,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'sort_order' => (int) ($payload['sort_order'] ?? ($maxSort + 1)),
        ]);
    }

    /**
     * @param  array{
     *     name_en: string,
     *     name_ur?: string|null,
     *     subject_en: string,
     *     subject_ur?: string|null,
     *     body_en: string,
     *     body_ur?: string|null,
     *     is_active?: bool
     * }  $payload
     */
    public function updateTemplate(ContactMessageTemplate $template, array $payload): ContactMessageTemplate
    {
        $normalized = $this->normalizePayload($payload);
        $this->assertValidContent($normalized);

        $template->fill([
            'name_en' => $normalized['name_en'],
            'name_ur' => $normalized['name_ur'],
            'subject_en' => $normalized['subject_en'],
            'subject_ur' => $normalized['subject_ur'],
            'body_en' => $normalized['body_en'],
            'body_ur' => $normalized['body_ur'],
            'is_active' => array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : $template->is_active,
        ]);

        if ($template->key !== 'welcome' && $template->isDirty('name_en')) {
            $template->key = $this->uniqueKey($normalized['name_en'], $template->id);
        }

        $template->save();

        return $template->fresh();
    }

    /**
     * Update only the active UI locale subject/body (used from Send message panel).
     */
    public function updateLocalizedContent(
        ContactMessageTemplate $template,
        string $subject,
        string $body,
        ?string $locale = null,
    ): ContactMessageTemplate {
        $locale ??= app()->getLocale();
        $subject = trim($subject);
        $body = (string) $body;

        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 200) {
            throw ValidationException::withMessages([
                'subject' => [__('validation.between.string', [
                    'attribute' => __('contact_message_templates.fields.subject'),
                    'min' => 3,
                    'max' => 200,
                ])],
            ]);
        }

        if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'body' => [__('validation.between.string', [
                    'attribute' => __('contact_message_templates.fields.body'),
                    'min' => 2,
                    'max' => 5000,
                ])],
            ]);
        }

        if ($locale === 'ur') {
            $template->update([
                'subject_ur' => $subject,
                'body_ur' => $body,
            ]);
        } else {
            $template->update([
                'subject_en' => $subject,
                'body_en' => $body,
            ]);
        }

        return $template->fresh();
    }

    public function deleteTemplate(ContactMessageTemplate $template): void
    {
        if ($template->key === 'welcome') {
            throw ValidationException::withMessages([
                'template' => [__('contact_message_templates.errors.welcome_locked')],
            ]);
        }

        $template->delete();
    }

    public function toggleActive(ContactMessageTemplate $template): ContactMessageTemplate
    {
        $template->update(['is_active' => ! $template->is_active]);

        return $template->fresh();
    }

    public function move(ContactMessageTemplate $template, string $direction): void
    {
        $items = ContactMessageTemplate::query()
            ->forAudience($template->audience)
            ->ordered()
            ->get();

        $index = $items->search(static fn (ContactMessageTemplate $row): bool => $row->id === $template->id);

        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return;
        }

        $current = $items[$index];
        $other = $items[$targetIndex];
        $currentSort = $current->sort_order;

        $current->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentSort]);
    }

    public function uniqueKey(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'template';
        }

        $base = Str::limit($base, 70, '');
        $key = $base;
        $suffix = 2;

        while (
            ContactMessageTemplate::query()
                ->where('key', $key)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $key = Str::limit($base, 70, '').'-'.$suffix;
            $suffix++;
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{name_en: string, name_ur: ?string, subject_en: string, subject_ur: ?string, body_en: string, body_ur: ?string}
     */
    private function normalizePayload(array $payload): array
    {
        $nameUr = trim((string) ($payload['name_ur'] ?? ''));
        $subjectUr = trim((string) ($payload['subject_ur'] ?? ''));
        $bodyUr = trim((string) ($payload['body_ur'] ?? ''));

        return [
            'name_en' => trim((string) ($payload['name_en'] ?? '')),
            'name_ur' => $nameUr !== '' ? $nameUr : null,
            'subject_en' => trim((string) ($payload['subject_en'] ?? '')),
            'subject_ur' => $subjectUr !== '' ? $subjectUr : null,
            'body_en' => (string) ($payload['body_en'] ?? ''),
            'body_ur' => $bodyUr !== '' ? $bodyUr : null,
        ];
    }

    /**
     * @param  array{name_en: string, name_ur: ?string, subject_en: string, subject_ur: ?string, body_en: string, body_ur: ?string}  $payload
     */
    private function assertValidContent(array $payload): void
    {
        $errors = [];

        if (mb_strlen($payload['name_en']) < 2 || mb_strlen($payload['name_en']) > 120) {
            $errors['name_en'] = [__('validation.between.string', [
                'attribute' => __('contact_message_templates.fields.name_en'),
                'min' => 2,
                'max' => 120,
            ])];
        }

        if ($payload['name_ur'] !== null && (mb_strlen($payload['name_ur']) < 2 || mb_strlen($payload['name_ur']) > 120)) {
            $errors['name_ur'] = [__('validation.between.string', [
                'attribute' => __('contact_message_templates.fields.name_ur'),
                'min' => 2,
                'max' => 120,
            ])];
        }

        if (mb_strlen($payload['subject_en']) < 3 || mb_strlen($payload['subject_en']) > 200) {
            $errors['subject_en'] = [__('validation.between.string', [
                'attribute' => __('contact_message_templates.fields.subject_en'),
                'min' => 3,
                'max' => 200,
            ])];
        }

        if ($payload['subject_ur'] !== null && (mb_strlen($payload['subject_ur']) < 3 || mb_strlen($payload['subject_ur']) > 200)) {
            $errors['subject_ur'] = [__('validation.between.string', [
                'attribute' => __('contact_message_templates.fields.subject_ur'),
                'min' => 3,
                'max' => 200,
            ])];
        }

        if (mb_strlen($payload['body_en']) < 2 || mb_strlen($payload['body_en']) > 5000) {
            $errors['body_en'] = [__('validation.between.string', [
                'attribute' => __('contact_message_templates.fields.body_en'),
                'min' => 2,
                'max' => 5000,
            ])];
        }

        if ($payload['body_ur'] !== null && (mb_strlen($payload['body_ur']) < 2 || mb_strlen($payload['body_ur']) > 5000)) {
            $errors['body_ur'] = [__('validation.between.string', [
                'attribute' => __('contact_message_templates.fields.body_ur'),
                'min' => 2,
                'max' => 5000,
            ])];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
