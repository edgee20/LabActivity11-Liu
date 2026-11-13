<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = collect([
            'Purok Liu',
            'Urgent',
            'Community',
            'Announcement',
            'Events',
        ])->mapWithKeys(fn (string $name) => [$name => Tag::create(['name' => $name])]);

        $pinnedPost = Post::where('is_pinned', true)->firstOrFail();
        $pinnedPost->tags()->sync([
            $tags['Purok Liu']->id,
            $tags['Urgent']->id,
        ]);

        Post::where('is_published', true)
            ->where('id', '!=', $pinnedPost->id)
            ->each(function (Post $post) use ($tags): void {
                $post->tags()->sync($tags->random(random_int(1, 3))->pluck('id')->all());
            });
    }
}
