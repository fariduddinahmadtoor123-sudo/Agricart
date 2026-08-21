<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreBlogService;
use App\Services\Store\StoreLayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BlogController extends Controller
{
    public function index(StoreLayoutService $layout, StoreBlogService $blog): View
    {
        return view('store.blog.index', [
            ...$layout->layoutData(),
            'posts' => $blog->publishedPaginated(12),
            'blog' => $blog,
        ]);
    }

    public function show(string $slug, StoreLayoutService $layout, StoreBlogService $blog): View|RedirectResponse
    {
        $post = $blog->findPublishedBySlug($slug);

        if ($post === null) {
            abort(404);
        }

        return view('store.blog.show', [
            ...$layout->layoutData(),
            'post' => $post,
            'related' => $blog->related($post),
            'blog' => $blog,
            'youtubeId' => $post->isVideo() ? $blog->extractYoutubeId($post->youtube_url) : null,
        ]);
    }
}
