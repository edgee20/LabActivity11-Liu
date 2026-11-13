<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $lola = User::where('email', 'lola.liu@bulletin.test')->firstOrFail();
        $pinnedPost = Post::where('is_pinned', true)->firstOrFail();

        Post::where('is_published', true)
            ->where('id', '!=', $pinnedPost->id)
            ->each(function (Post $post) use ($users): void {
                $count = random_int(0, 5);

                if ($count > 0) {
                    Comment::factory()
                        ->count($count)
                        ->for($post)
                        ->state(fn (): array => ['user_id' => $users->random()->id])
                        ->create();
                }
            });

        Comment::factory()
            ->for($pinnedPost)
            ->for($lola, 'user')
            ->create();

        Comment::factory()
            ->count(2)
            ->for($pinnedPost)
            ->state(fn (): array => [
                'user_id' => $users->where('id', '!=', $lola->id)->random()->id,
            ])
            ->create();
    }
}
