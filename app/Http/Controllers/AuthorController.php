<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function show(Request $request, Author $author): View
    {
        $author->load([
            'posts' => fn ($query) => $query
                ->where('is_published', true)
                ->with(['author', 'tags'])
                ->latest('published_at'),
        ]);
        $author->loadCount('comments');

        $drafts = $request->user() !== null && $request->user()->id === $author->user_id
            ? $author->posts()->where('is_published', false)->latest()->get()
            : collect();

        return view('authors.show', compact('author', 'drafts'));
    }
}
