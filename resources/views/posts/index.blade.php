@extends('layouts.app')

@section('title', 'Barangay Bulletin – Purok Liu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-blue-900">Barangay Bulletin</h1>
            <p class="mt-2 text-gray-600">Announcements and updates from Purok Liu.</p>
        </div>

        <form method="GET" action="{{ route('posts.index') }}" class="mb-8 flex flex-col gap-2 rounded-lg bg-white p-4 shadow-sm sm:flex-row">
            <label class="sr-only" for="q">Search title</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Search title" class="rounded-md border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:flex-1">

            <label class="sr-only" for="category">Category</label>
            <select id="category" name="category" class="rounded-md border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All categories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-md bg-blue-700 px-5 py-2 font-medium text-white hover:bg-blue-800">
                Filter
            </button>
        </form>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($posts as $post)
                <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">{{ $post->category }}</span>
                                @if ($post->is_pinned)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Pinned</span>
                                @endif
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">
                                <a href="{{ route('posts.show', $post->id) }}" class="hover:text-blue-700">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-2 text-sm text-gray-500">
                                Published {{ $post->published_at?->format('F j, Y') }} · {{ $post->views }} views
                            </p>
                        </div>
                        <span class="text-sm text-gray-500">Author ID: {{ $post->author_id }}</span>
                    </div>
                </article>
            @empty
                <p class="rounded-lg bg-white p-6 text-center sm:col-span-2 lg:col-span-3 text-gray-600 shadow-sm">No posts match your search.</p>
            @endforelse
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
        @if ($posts->total() > 0)
            <p class="mt-3 text-sm text-gray-600">
                Showing {{ $posts->firstItem() }}–{{ $posts->lastItem() }} of {{ $posts->total() }} posts
            </p>
        @endif
    </main>
@endsection
