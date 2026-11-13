@extends('layouts.app')

@section('title', $author->name . ' – Barangay Bulletin – Purok Liu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('posts.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">&larr; Back to bulletin</a>

        <header class="mt-6 rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-3xl font-bold tracking-tight text-blue-900">{{ $author->name }}</h1>
            <p class="mt-2 text-gray-600">{{ $author->position }}</p>
            <p class="mt-4 text-sm text-gray-500">{{ $author->comments_count }} comments received</p>
        </header>

        <section class="mt-8">
            <h2 class="text-2xl font-bold text-blue-900">Published posts</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($author->posts as $post)
                    <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">{{ $post->category }}</span>
                            @if ($post->is_pinned)
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Pinned</span>
                            @endif
                        </div>
                        <h3 class="mt-3 text-xl font-semibold text-gray-900">
                            <a href="{{ route('posts.show', $post) }}" class="hover:text-blue-700">{{ $post->title }}</a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Published {{ $post->published_at?->format('F j, Y') }} · {{ $post->views }} views
                        </p>
                    </article>
                @empty
                    <p class="rounded-lg bg-white p-6 text-gray-600 shadow-sm sm:col-span-2 lg:col-span-3">No published posts yet.</p>
                @endforelse
            </div>
        </section>
    </main>
@endsection
