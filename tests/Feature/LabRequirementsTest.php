<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LabRequirementsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_migrations_can_be_fully_reversed_and_rebuilt_with_seed_data(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate:reset', ['--no-interaction' => true])->assertExitCode(0);
        foreach (['authors', 'posts', 'comments', 'tags', 'post_tag', 'users'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->artisan('migrate:fresh', ['--seed' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertDatabaseCount('users', 10);
        $this->assertDatabaseCount('tags', 5);
        $this->assertDatabaseCount('posts', 51);
        $this->assertDatabaseCount('authors', 5);
        $this->assertSame('2026-04-20', Post::where('is_pinned', true)->sole()->published_at->toDateString());
    }

    public function test_relationships_work_in_both_directions_and_through_posts(): void
    {
        $owner = User::factory()->has(Author::factory(), 'author')->create();
        $author = $owner->author()->sole();
        $post = Post::factory()->for($author)->create();
        $resident = User::factory()->create();
        $comment = Comment::factory()->for($post)->for($resident, 'user')->create();
        $tag = Tag::create(['name' => 'Libre']);
        $post->tags()->sync([$tag->id]);
        $this->assertTrue($author->user()->sole()->is($owner));
        $this->assertTrue($author->posts()->sole()->is($post));
        $this->assertTrue($post->author()->sole()->is($author));
        $this->assertTrue($post->comments()->sole()->is($comment));
        $this->assertTrue($comment->post()->sole()->is($post));
        $this->assertTrue($comment->user()->sole()->is($resident));
        $this->assertTrue($resident->comments()->sole()->is($comment));
        $this->assertTrue($author->comments()->sole()->is($comment));
        $this->assertTrue($post->tags()->sole()->is($tag));
        $this->assertTrue($tag->posts()->sole()->is($post));
    }

    public function test_deleting_a_user_nulls_the_author_link_and_removes_their_comments(): void
    {
        $user = User::factory()->has(Author::factory(), 'author')->create();
        $author = $user->author()->sole();
        $post = Post::factory()->for($author)->create();
        Comment::factory()->for($post)->for($user)->create();
        $user->delete();
        $this->assertNull($author->fresh()->user_id);
        $this->assertDatabaseCount('comments', 0);
        $this->assertModelExists($post);
        $this->assertModelExists($author);
    }

    public function test_deleting_posts_and_tags_cascades_pivot_rows_and_comments(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::create(['name' => 'Urgent']);
        $post->tags()->sync([$tag->id]);
        $tag->delete();
        $this->assertDatabaseCount('post_tag', 0);
        $this->assertModelExists($post);
        $tag = Tag::create(['name' => 'Schedule']);
        $post->tags()->sync([$tag->id]);
        Comment::factory()->for($post)->for(User::factory())->create();
        $post->delete();
        $this->assertDatabaseCount('post_tag', 0);
        $this->assertDatabaseCount('comments', 0);
        $this->assertModelExists($tag);
    }

    public function test_a_user_cannot_have_two_author_profiles(): void
    {
        $user = User::factory()->has(Author::factory(), 'author')->create();
        $this->expectException(QueryException::class);
        Author::factory()->for($user)->create();
    }

    public function test_the_pivot_primary_key_prevents_duplicate_post_tag_pairs(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::create(['name' => 'Community']);
        $post->tags()->attach($tag);
        $this->expectException(QueryException::class);
        $post->tags()->attach($tag);
    }

    public function test_author_post_cards_include_tags_and_author_links(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::create(['name' => 'Author card tag']);
        $post->tags()->sync([$tag->id]);
        $this->get(route('authors.show', $post->author))->assertOk()
            ->assertSee('Author card tag')
            ->assertSee('href="'.route('authors.show', $post->author).'"', false);
    }

    public function test_navigation_and_comment_forms_match_guest_resident_and_author_roles(): void
    {
        $post = Post::factory()->create();
        $this->get(route('posts.index'))->assertOk()->assertDontSee('New post');
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Sign in to comment')->assertDontSee('Leave a comment');
        $this->actingAs(User::factory()->create())->get(route('posts.index'))->assertOk()->assertDontSee('New post');
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Leave a comment')->assertSee('name="_token"', false);
        $this->followingRedirects()->get(route('login'))->assertOk()->assertViewIs('posts.index');
        $this->actingAs(User::factory()->has(Author::factory(), 'author')->create())->get(route('posts.index'))->assertOk()->assertSee('New post');
    }

    public function test_comments_display_newest_first_with_names_and_escaped_content(): void
    {
        $post = Post::factory()->create();
        $resident = User::factory()->create(['name' => 'Resident Liu']);
        Comment::factory()->for($post)->for($resident, 'user')->create([
            'body' => 'Earlier test comment', 'created_at' => now()->subHour(),
        ]);
        Comment::factory()->for($post)->for($resident, 'user')->create([
            'body' => 'Recent test comment <script>alert(1)</script>', 'created_at' => now(),
        ]);
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Resident Liu')
            ->assertSeeInOrder(['Recent test comment', 'Earlier test comment'])
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
