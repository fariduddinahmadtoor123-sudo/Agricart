<?php

namespace Tests\Feature\Store;

use App\Models\StoreBlogPost;
use App\Services\Store\StoreBlogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_nav_and_index_show_published_posts_only(): void
    {
        StoreBlogPost::query()->create([
            'title_en' => 'Published Guide',
            'slug' => 'published-guide',
            'type' => StoreBlogPost::TYPE_ARTICLE,
            'is_published' => true,
            'published_at' => now()->subHour(),
            'sort_order' => 1,
        ]);

        StoreBlogPost::query()->create([
            'title_en' => 'Draft Hidden',
            'slug' => 'draft-hidden',
            'type' => StoreBlogPost::TYPE_ARTICLE,
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.blog.nav'), false)
            ->assertSee(route('store.blog', absolute: false), false);

        $this->get(route('store.blog'))
            ->assertOk()
            ->assertSee('Published Guide', false)
            ->assertDontSee('Draft Hidden', false);
    }

    public function test_video_post_links_to_youtube_with_thumbnail(): void
    {
        StoreBlogPost::query()->create([
            'title_en' => 'Tractor Tips',
            'slug' => 'tractor-tips',
            'type' => StoreBlogPost::TYPE_VIDEO,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'sort_order' => 1,
        ]);

        $this->get(route('store.blog'))
            ->assertOk()
            ->assertSee('img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)
            ->assertSee('youtube.com/watch?v=dQw4w9WgXcQ&amp;autoplay=1', false)
            ->assertSee('target="_blank"', false);

        $this->get(route('store.blog.show', 'tractor-tips'))
            ->assertOk()
            ->assertSee('Tractor Tips', false)
            ->assertSee('youtube.com/watch?v=dQw4w9WgXcQ&amp;autoplay=1', false);
    }

    public function test_youtube_id_extraction(): void
    {
        $service = app(StoreBlogService::class);

        $this->assertSame('abc123XY', $service->extractYoutubeId('https://youtu.be/abc123XY'));
        $this->assertSame('abc123XY', $service->extractYoutubeId('https://www.youtube.com/watch?v=abc123XY&t=10'));
        $this->assertSame('abc123XY', $service->extractYoutubeId('https://www.youtube.com/shorts/abc123XY'));
        $this->assertNull($service->extractYoutubeId('https://example.com/video'));
    }

    public function test_unpublished_slug_returns_404(): void
    {
        StoreBlogPost::query()->create([
            'title_en' => 'Secret',
            'slug' => 'secret',
            'type' => StoreBlogPost::TYPE_ARTICLE,
            'is_published' => false,
            'sort_order' => 1,
        ]);

        $this->get(route('store.blog.show', 'secret'))->assertNotFound();
    }
}
