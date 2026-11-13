<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPostRequest;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(IndexPostRequest $request): View
    {
        $posts = Post::query()
            ->with(['author', 'tags'])
            ->withCount('comments')
            ->where('is_published', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->input('q').'%');
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->input('category'));
            })
            ->when($request->filled('tag'), function ($query) use ($request) {
                $query->whereHas('tags', function ($tagQuery) use ($request) {
                    $tagQuery->where('name', $request->input('tag'));
                });
            })
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate(6)
            ->withQueryString();

        $categories = Post::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $tags = Tag::query()->orderBy('name')->get();

        $canCreatePosts = auth()->check() && auth()->user()->author()->exists();

        return view('posts.index', compact('posts', 'categories', 'tags', 'canCreatePosts'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->author()->exists(), 403);

        return view('posts.create', [
            'tags' => Tag::query()->orderBy('name')->get(),
            'categories' => ['Announcement', 'Health', 'Peace & Order', 'Ayuda', 'Community'],
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tags'];
        unset($validated['tags']);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);
        $validated['published_at'] = $validated['is_published'] ? now() : null;

        $post = DB::transaction(function () use ($request, $validated, $tagIds): Post {
            $post = $request->user()->author()->firstOrFail()->posts()->create($validated);
            $post->tags()->sync($tagIds);

            return $post;
        });

        return redirect()->route('posts.show', $post)
            ->with('status', $post->is_published ? 'Post published.' : 'Post saved as a draft. Only you can preview it.');
    }

    public function show(Request $request, int $id): View
    {
        $post = Post::findOrFail($id);

        abort_unless($post->is_published || $request->user()?->author()->whereKey($post->author_id)->exists(), 404);

        if ($post->is_published) {
            $post->increment('views');
        }
        $post->load(['author', 'tags', 'comments.user']);
        $post->setRelation('comments', $post->comments->sortByDesc('created_at')->values());

        return view('posts.show', compact('post'));
    }
}
