<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request)
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

    public function create(): View
    {
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

        $post = $request->user()->author()->firstOrFail()->posts()->create($validated);
        $post->tags()->sync($tagIds);

        return redirect()->route('posts.show', $post);
    }

    public function show(int $id): View
    {
        $post = Post::findOrFail($id);

        abort_unless($post->is_published, 404);

        $post->views++;
        $post->save();
        $post->load(['author', 'tags', 'comments.user']);
        $post->setRelation('comments', $post->comments->sortByDesc('created_at')->values());

        return view('posts.show', compact('post'));
    }
}
