@extends('layouts.app')

@section('title', 'New post – Barangay Bulletin – Purok Liu')

@section('content')
    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('posts.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">&larr; Back to bulletin</a>

        <section class="mt-6 rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-3xl font-bold tracking-tight text-blue-900">Create a new post</h1>

            @if ($errors->any())
                <div class="mt-6 rounded-md bg-red-100 p-4 text-red-800">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('posts.store') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input id="title" name="title" type="text" maxlength="120" value="{{ is_string(old('title')) ? old('title') : '' }}" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700">Body</label>
                    <textarea id="body" name="body" rows="8" minlength="20" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ is_string(old('body')) ? old('body') : '' }}</textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category" name="category" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-gray-700">Tags</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($tags as $tag)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                    @checked(in_array($tag->id, (array) old('tags', [])))
                                    class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('tags')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('tags.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div>
                    <input type="hidden" name="is_published" value="0">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1"
                            @checked(in_array(old('is_published', '1'), [1, '1', true], true))
                            class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                        Publish now
                    </label>
                    <p class="mt-2 text-sm text-gray-500">Uncheck to save a private draft. You can find drafts on your author page.</p>
                    @error('is_published')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-md bg-blue-700 px-5 py-2 font-medium text-white hover:bg-blue-800">
                    Create post
                </button>
            </form>
        </section>
    </main>
@endsection
