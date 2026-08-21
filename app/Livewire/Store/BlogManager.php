<?php

namespace App\Livewire\Store;

use App\Models\StoreBlogPost;
use App\Models\User;
use App\Rules\SafeUrl;
use App\Services\Access\ModulePageAccess;
use App\Services\Media\OptimizedImageStorage;
use App\Services\Store\StoreBlogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class BlogManager extends Component
{
    use WithFileUploads;

    public string $title_en = '';

    public string $title_ur = '';

    public string $slug = '';

    public string $excerpt_en = '';

    public string $excerpt_ur = '';

    public string $body_en = '';

    public string $body_ur = '';

    public string $type = StoreBlogPost::TYPE_ARTICLE;

    public string $youtube_url = '';

    public $coverUpload = null;

    public bool $is_published = true;

    public ?string $published_at = null;

    public bool $slugManuallyEdited = false;

    public ?int $editingId = null;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);
    }

    public function updatedTitleEn(string $value): void
    {
        if ($this->editingId !== null || $this->slugManuallyEdited) {
            return;
        }

        $this->slug = Str::slug($value);
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function savePost(): void
    {
        abort_unless($this->userCan($this->editingId ? 'edit' : 'create'), 403);

        $rules = [
            'title_en' => ['required', 'string', 'max:255'],
            'title_ur' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'excerpt_ur' => ['nullable', 'string', 'max:500'],
            'body_en' => ['nullable', 'string', 'max:50000'],
            'body_ur' => ['nullable', 'string', 'max:50000'],
            'type' => ['required', Rule::in(StoreBlogPost::TYPES)],
            'youtube_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'coverUpload' => ['nullable', 'image', 'max:4096'],
        ];

        if ($this->type === StoreBlogPost::TYPE_VIDEO) {
            $rules['youtube_url'] = ['required', 'string', 'max:500', new SafeUrl];
        }

        $this->validate($rules);

        $blog = app(StoreBlogService::class);

        if ($this->type === StoreBlogPost::TYPE_VIDEO && $blog->extractYoutubeId($this->youtube_url) === null) {
            $this->addError('youtube_url', __('agricart.store.admin.blog.youtube_invalid'));

            return;
        }

        $normalized = filled(trim($this->slug))
            ? Str::slug($this->slug)
            : Str::slug($this->title_en);

        if (! filled($normalized)) {
            $this->addError('title_en', __('agricart.store.admin.blog.slug_from_title'));

            return;
        }

        $slug = $blog->uniqueSlug($normalized, $this->editingId);
        $publishedAt = null;

        if ($this->is_published) {
            $publishedAt = filled($this->published_at)
                ? $this->published_at
                : now()->toDateTimeString();
        }

        $payload = [
            'title_en' => $this->title_en,
            'title_ur' => filled($this->title_ur) ? $this->title_ur : null,
            'slug' => $slug,
            'excerpt_en' => filled($this->excerpt_en) ? $this->excerpt_en : null,
            'excerpt_ur' => filled($this->excerpt_ur) ? $this->excerpt_ur : null,
            'body_en' => filled($this->body_en) ? $this->body_en : null,
            'body_ur' => filled($this->body_ur) ? $this->body_ur : null,
            'type' => $this->type,
            'youtube_url' => $this->type === StoreBlogPost::TYPE_VIDEO && filled($this->youtube_url)
                ? $this->youtube_url
                : null,
            'is_published' => $this->is_published,
            'published_at' => $publishedAt,
        ];

        if ($this->editingId) {
            $post = StoreBlogPost::query()->findOrFail($this->editingId);
            $post->update($payload);

            if ($this->coverUpload !== null) {
                $this->replaceCover($post);
            }
        } else {
            $maxSort = (int) StoreBlogPost::query()->max('sort_order');
            $path = null;

            if ($this->coverUpload !== null) {
                $path = app(OptimizedImageStorage::class)->store($this->coverUpload, 'store/blog');
            }

            StoreBlogPost::query()->create([
                ...$payload,
                'cover_image_path' => $path,
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->resetForm();
        $this->statusMessage = __('agricart.store.admin.saved');
        $this->statusType = 'success';
    }

    public function editPost(int $id): void
    {
        abort_unless($this->userCan('edit'), 403);
        $post = StoreBlogPost::query()->findOrFail($id);

        $this->editingId = $post->id;
        $this->title_en = $post->title_en;
        $this->title_ur = $post->title_ur ?? '';
        $this->slug = $post->slug;
        $this->excerpt_en = $post->excerpt_en ?? '';
        $this->excerpt_ur = $post->excerpt_ur ?? '';
        $this->body_en = $post->body_en ?? '';
        $this->body_ur = $post->body_ur ?? '';
        $this->type = $post->type;
        $this->youtube_url = $post->youtube_url ?? '';
        $this->is_published = $post->is_published;
        $this->published_at = $post->published_at?->format('Y-m-d\TH:i') ?? null;
        $this->coverUpload = null;
        $this->slugManuallyEdited = true;
        $this->statusMessage = null;

        $this->js('document.getElementById("store-blog-post-form")?.scrollIntoView({behavior:"smooth",block:"start"})');
    }

    public function deletePost(int $id): void
    {
        abort_unless($this->userCan('delete'), 403);
        $post = StoreBlogPost::query()->findOrFail($id);
        $this->deleteCover($post);
        $post->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->statusMessage = __('agricart.store.admin.saved');
        $this->statusType = 'success';
    }

    public function togglePublished(int $id): void
    {
        abort_unless($this->userCan('edit'), 403);
        $post = StoreBlogPost::query()->findOrFail($id);
        $next = ! $post->is_published;
        $post->update([
            'is_published' => $next,
            'published_at' => $next ? ($post->published_at ?? now()) : $post->published_at,
        ]);

        $this->statusMessage = __('agricart.store.admin.saved');
        $this->statusType = 'success';
    }

    public function resetForm(): void
    {
        abort_unless($this->userCan('view'), 403);
        $this->editingId = null;
        $this->title_en = '';
        $this->title_ur = '';
        $this->slug = '';
        $this->excerpt_en = '';
        $this->excerpt_ur = '';
        $this->body_en = '';
        $this->body_ur = '';
        $this->type = StoreBlogPost::TYPE_ARTICLE;
        $this->youtube_url = '';
        $this->coverUpload = null;
        $this->is_published = true;
        $this->published_at = null;
        $this->slugManuallyEdited = false;
    }

    public function render(): View
    {
        $blog = app(StoreBlogService::class);

        return view('livewire.store.blog-manager', [
            'posts' => $this->userCan('view')
                ? StoreBlogPost::query()->orderByDesc('published_at')->orderByDesc('id')->get()
                : collect(),
            'canCreate' => $this->userCan('create'),
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
            'blog' => $blog,
        ]);
    }

    private function replaceCover(StoreBlogPost $post): void
    {
        if ($this->coverUpload === null) {
            return;
        }

        $this->deleteCover($post);
        $path = app(OptimizedImageStorage::class)->store($this->coverUpload, 'store/blog');
        $post->update(['cover_image_path' => $path]);
        $this->coverUpload = null;
    }

    private function deleteCover(StoreBlogPost $post): void
    {
        if (! filled($post->cover_image_path)) {
            return;
        }

        Storage::disk('public')->delete($post->cover_image_path);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || ModulePageAccess::can($user, 'store', 'blog', $action);
    }
}
