<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function show(Author $author): View
    {
        $author->load([
            'posts' => fn ($query) => $query
                ->where('is_published', true)
                ->with(['author', 'tags'])
                ->latest('published_at'),
        ]);
        $author->loadCount('comments');

        return view('authors.show', compact('author'));
    }
}
