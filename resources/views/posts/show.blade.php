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
                Published {{ $post->published_at?->format('F j, Y') }} · {{ $post->views }} views · Author ID: {{ $post->author_id }}
            </p>
            <div class="mt-8 whitespace-pre-line leading-7 text-gray-700">{{ $post->body }}</div>
        </article>
    </main>
@endsection
