<?php

namespace App\Services\Store;

use App\Models\StoreBlogPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StoreBlogService
{
    /**
     * @return LengthAwarePaginator<int, StoreBlogPost>
     */
    public function publishedPaginated(int $perPage = 12): LengthAwarePaginator
    {
        return StoreBlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): ?StoreBlogPost
    {
        return StoreBlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @return Collection<int, StoreBlogPost>
     */
    public function related(StoreBlogPost $post, int $limit = 3): Collection
    {
        return StoreBlogPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function extractYoutubeId(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i', $url, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[A-Za-z0-9_-]{6,}$/', $url) === 1) {
            return $url;
        }

        return null;
    }

    public function youtubeThumbnailUrl(?string $videoId): ?string
    {
        if (! filled($videoId)) {
            return null;
        }

        return 'https://img.youtube.com/vi/'.$videoId.'/hqdefault.jpg';
    }

    public function youtubeWatchUrl(?string $videoId, bool $autoplay = true): ?string
    {
        if (! filled($videoId)) {
            return null;
        }

        $url = 'https://www.youtube.com/watch?v='.$videoId;

        return $autoplay ? $url.'&autoplay=1' : $url;
    }

    public function cardImageUrl(StoreBlogPost $post): ?string
    {
        if ($cover = $post->coverImageUrl()) {
            return $cover;
        }

        if ($post->isVideo()) {
            return $this->youtubeThumbnailUrl($this->extractYoutubeId($post->youtube_url));
        }

        return null;
    }

    public function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $slug = filled($slug) ? $slug : 'post';
        $candidate = $slug;
        $i = 2;

        while (
            StoreBlogPost::query()
                ->when($ignoreId !== null, static fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $slug.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
