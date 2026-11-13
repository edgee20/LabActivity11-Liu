@extends('layouts.app')

@section('title', $post->title . ' – Barangay Bulletin – Purok Liu')

@section('content')
    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('posts.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">&larr; Back to bulletin</a>

        <article class="mt-6 rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">{{ $post->category }}</span>
                @if ($post->is_pinned)
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Pinned</span>
                @endif
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-blue-900">{{ $post->title }}</h1>
            <p class="mt-3 text-sm text-gray-500">
                Published {{ $post->published_at?->format('F j, Y') }} · {{ $post->views }} views ·
                By <a href="{{ route('authors.show', $post->author) }}" class="text-blue-700 hover:text-blue-900">{{ $post->author->name }}</a>
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($post->tags as $tag)
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700">{{ $tag->name }}</span>
                @endforeach
            </div>
            <div class="mt-8 whitespace-pre-line leading-7 text-gray-700">{{ $post->body }}</div>
        </article>

        <section class="mt-8 rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-2xl font-bold text-blue-900">Comments</h2>
            <div class="mt-6 space-y-5">
                @forelse ($post->comments as $comment)
                    <article class="border-b border-gray-200 pb-5 last:border-0 last:pb-0">
                        <p class="font-medium text-gray-900">{{ $comment->user->name }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $comment->created_at->format('F j, Y g:i A') }}</p>
                        <p class="mt-3 text-gray-700">{{ $comment->body }}</p>
                    </article>
                @empty
                    <p class="text-gray-600">No comments yet.</p>
                @endforelse
            </div>
        </section>

        @if (session('status'))
            <p class="mt-8 rounded-md bg-green-100 px-4 py-3 text-green-800">{{ session('status') }}</p>
        @endif

        @auth
            <section class="mt-8 rounded-lg bg-white p-6 shadow-sm sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-md bg-red-100 p-4 text-red-800">
                        <p class="font-semibold">Please fix the following errors:</p>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h2 class="text-2xl font-bold text-blue-900">Leave a comment</h2>
                <form method="POST" action="{{ route('comments.store', $post) }}" class="mt-6">
                    @csrf
                    <label for="body" class="block text-sm font-medium text-gray-700">Comment</label>
                    <textarea id="body" name="body" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-4 rounded-md bg-blue-700 px-5 py-2 font-medium text-white hover:bg-blue-800">
                        Post comment
                    </button>
                </form>
            </section>
        @else
            <p class="mt-8 rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                <a href="{{ route('login') }}" class="font-medium text-blue-700 hover:text-blue-900">Sign in to comment</a>
            </p>
        @endauth
    </main>
@endsection
