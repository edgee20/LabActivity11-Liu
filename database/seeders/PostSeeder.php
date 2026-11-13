<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $authors = Author::all();

        Post::factory()->count(40)
            ->sequence(fn ($sequence) => ['author_id' => $authors->random()->id])
            ->create();

        Post::factory()->count(10)->draft()
            ->sequence(fn ($sequence) => ['author_id' => $authors->random()->id])
            ->create();

        $kagawad = Author::where('email', 'kagawad.liu@bulletin.test')->firstOrFail();

        Post::create([
            'author_id' => $kagawad->id,
            'title' => 'Purok Liu Clean-up Drive',
            'category' => 'Community',
            'body' => 'Join Kagawad Ej Liu and our neighbors for the Purok Liu Clean-up Drive.',
            'is_published' => true,
            'is_pinned' => true,
            'published_at' => '2026-04-20 08:00:00',
        ]);
    }
}
