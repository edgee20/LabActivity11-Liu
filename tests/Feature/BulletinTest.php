<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BulletinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withCookie(config('session.cookie'), session()->getId());
    }

    private function authorUser(): User
    {
        return User::factory()->has(Author::factory(), 'author')->create();
    }

    /** @return array{title: string, body: string, category: string, tags: array<int, int>} */
    private function postData(): array
    {
        return [
            'title' => 'Purok Liu community meeting',
            'body' => 'Please join our community meeting at the barangay hall.',
            'category' => 'Community',
            'tags' => [Tag::create(['name' => 'Purok Liu'])->id],
        ];
    }

    public function test_author_can_create_and_view_a_draft_without_a_404(): void
    {
        $user = $this->authorUser();
        $response = $this->actingAs($user)->post(route('posts.store'), $this->postData());
        $post = Post::sole();
        $response->assertRedirect(route('posts.show', $post))->assertSessionHas('status');
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        $this->assertSame(1, $post->tags()->count());
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Draft preview')->assertDontSee('Leave a comment');
        $this->assertSame(0, $post->fresh()->views);
        $this->get(route('authors.show', $user->author()->sole()))->assertOk()->assertSee($post->title);
    }

    public function test_author_can_publish_a_post_with_tags_and_only_validated_fields(): void
    {
        $user = $this->authorUser();
        $data = [...$this->postData(), 'is_published' => '1', 'is_pinned' => true, 'views' => 999];
        $response = $this->actingAs($user)->post(route('posts.store'), $data);
        $post = Post::sole();
        $response->assertRedirect(route('posts.show', $post));
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
        $this->assertFalse($post->is_pinned);
        $this->assertSame($user->author()->sole()->id, $post->author_id);
        $this->get(route('posts.show', $post))->assertOk()->assertSee($post->title);
        $this->assertSame(1, $post->fresh()->views);
    }

    public function test_drafts_are_hidden_from_guests_and_other_users_and_cannot_receive_comments(): void
    {
        $post = Post::factory()->draft()->create();
        $this->get(route('posts.show', $post))->assertNotFound();
        $this->get(route('posts.index'))->assertDontSee($post->title);
        $this->get(route('authors.show', $post->author))->assertDontSee($post->title);
        $this->actingAs($this->authorUser())->get(route('posts.show', $post))->assertNotFound();
        $this->post(route('comments.store', $post), ['body' => 'A hidden comment'])->assertNotFound();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_guests_and_residents_cannot_create_posts(): void
    {
        $this->get(route('posts.create'))->assertRedirect(route('login'));
        $this->post(route('posts.store'), $this->postData())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('posts.create'))->assertForbidden();
        $this->post(route('posts.store'), [])->assertForbidden();
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_validation_preserves_form_input_and_does_not_write_a_post(): void
    {
        $data = [...$this->postData(), 'title' => str_repeat('a', 121), 'body' => 'Too short', 'category' => 'Invalid', 'is_published' => '1'];
        $this->actingAs($this->authorUser())->from(route('posts.create'))->post(route('posts.store'), $data)
            ->assertRedirect(route('posts.create'))->assertSessionHasErrors(['title', 'body', 'category'])->assertSessionHasInput('tags', $data['tags']);
        $this->get(route('posts.create'))->assertOk()->assertSee('Too short')->assertSee('checked', false);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_tags_reject_missing_invalid_nested_and_duplicate_values(): void
    {
        $user = $this->authorUser();
        $data = $this->postData();
        foreach ([[], [999], [[$data['tags'][0]]], [$data['tags'][0], $data['tags'][0]]] as $tags) {
            $this->actingAs($user)->post(route('posts.store'), [...$data, 'tags' => $tags])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_comments_use_the_signed_in_user_and_validate_with_old_input(): void
    {
        $post = Post::factory()->create();
        $resident = User::factory()->create();
        $this->post(route('comments.store', $post), ['body' => 'Hello'])->assertRedirect(route('login'));
        $this->actingAs($resident)->from(route('posts.show', $post))->post(route('comments.store', $post), ['body' => 'Hi'])
            ->assertSessionHasErrors(['body' => 'Purok Liu comments need at least 3 characters.'])->assertSessionHasInput('body', 'Hi');
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Purok Liu comments need at least 3 characters.')->assertDontSee('minlength="3"', false);
        foreach (['', str_repeat('a', 1001), ['invalid']] as $body) {
            $this->post(route('comments.store', $post), ['body' => $body])->assertSessionHasErrors('body');
        }
        $this->post(route('comments.store', $post), ['body' => 'Thank you for the update!', 'user_id' => 999])
            ->assertRedirect(route('posts.show', $post))->assertSessionHas('status', 'Comment posted.');
        $this->assertSame($resident->id, Comment::sole()->user_id);
        $this->get(route('authors.show', $post->author))->assertOk()->assertSee('1 comments received');
    }

    public function test_index_filters_pagination_pinned_order_and_eager_loaded_pages(): void
    {
        $author = Author::factory()->create();
        $tag = Tag::create(['name' => 'Urgent']);
        $posts = Post::factory()->count(8)->for($author)->create(['title' => 'Meeting update', 'category' => 'Community']);
        foreach ($posts as $post) {
            $post->tags()->sync([$tag->id]);
        }
        $posts->last()->update(['is_pinned' => true, 'published_at' => now()->subYear()]);
        Post::factory()->draft()->for($author)->create(['title' => 'Hidden draft']);
        $url = route('posts.index', ['q' => 'Meeting', 'category' => 'Community', 'tag' => 'Urgent']);
        $this->get($url)->assertOk()->assertViewHas('posts', function ($result) use ($posts): bool {
            return $result->total() === 8 && $result->count() === 6 && $result->first()->id === $posts->last()->id
                && str_contains($result->nextPageUrl(), 'tag=Urgent') && str_contains($result->nextPageUrl(), 'q=Meeting');
        });
        $this->get($url.'&page=2')->assertOk()->assertViewHas('posts', fn ($result): bool => $result->count() === 2);
        $this->get(route('posts.index', ['tag' => 'Missing']))->assertOk()->assertSee('No posts match your search.');
        $this->get(route('authors.show', $author))->assertOk()->assertDontSee('Hidden draft');
        foreach (['q', 'category', 'tag'] as $filter) {
            $this->get(route('posts.index', [$filter => ['invalid']]))->assertSessionHasErrors($filter);
        }
    }

    public function test_login_and_logout_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'bulletin2026']);
        $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'bulletin2026'])->assertRedirect(route('posts.index'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_seeded_data_matches_the_handout_and_renders_without_lazy_loading(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 10);
        $this->assertEqualsCanonicalizing(['Purok Liu', 'Urgent', 'Libre', 'Schedule', 'Community'], Tag::pluck('name')->all());
        $lola = User::where('email', 'lola.liu@bulletin.test')->sole();
        $kagawad = User::where('email', 'kagawad.liu@bulletin.test')->sole();
        $this->assertTrue(Hash::check('bulletin2026', $lola->password));
        $this->assertTrue(Hash::check('bulletin2026', $kagawad->password));
        $this->assertSame('Kagawad Ej Liu', $kagawad->author()->sole()->name);
        $pinned = Post::where('is_pinned', true)->sole();
        $this->assertSame('Purok Liu Clean-up Drive', $pinned->title);
        $this->assertEqualsCanonicalizing(['Purok Liu', 'Urgent'], $pinned->tags()->pluck('name')->all());
        $this->assertGreaterThanOrEqual(3, $pinned->comments()->count());
        $this->assertTrue($pinned->comments()->whereBelongsTo($lola)->exists());
        foreach (Post::withCount(['tags', 'comments'])->get() as $post) {
            $this->assertGreaterThanOrEqual(1, $post->tags_count);
            $this->assertLessThanOrEqual(3, $post->tags_count);
            $this->assertLessThanOrEqual(5, $post->comments_count);
        }
        $this->get(route('posts.index'))->assertOk();
        $this->get(route('posts.show', $pinned))->assertOk()->assertSee('Lola Liu');
        $this->get(route('authors.show', $pinned->author))->assertOk();
        $this->actingAs($kagawad)->get(route('posts.create'))->assertOk();
    }

    public function test_malformed_old_input_does_not_break_post_or_comment_forms(): void
    {
        $this->actingAs($this->authorUser())->from(route('posts.create'))->post(route('posts.store'), [
            'title' => ['invalid'], 'body' => ['invalid'], 'tags' => 'invalid', 'is_published' => ['invalid'],
        ])->assertSessionHasErrors(['title', 'body', 'tags', 'is_published']);
        $this->get(route('posts.create'))->assertOk()->assertSee('Please fix the following errors:');
        $post = Post::factory()->create();
        $this->from(route('posts.show', $post))->post(route('comments.store', $post), ['body' => ['invalid']])->assertSessionHasErrors('body');
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Please fix the following errors:');
    }

    public function test_tag_correction_preserves_existing_post_assignments_and_can_be_reversed(): void
    {
        $tag = Tag::create(['name' => 'Announcement']);
        $post = Post::factory()->create();
        $post->tags()->sync([$tag->id]);
        $migration = require database_path('migrations/2026_11_13_065525_align_bulletin_tag_names.php');
        $migration->up();
        $this->assertSame('Libre', $tag->fresh()->name);
        $this->assertSame($tag->id, $post->tags()->sole()->id);
        $migration->down();
        $this->assertSame('Announcement', $tag->fresh()->name);
    }

    public function test_tag_seeder_repairs_untagged_posts_without_changing_existing_assignments(): void
    {
        $this->seed();
        $post = Post::where('is_pinned', false)->where('is_published', true)->firstOrFail();
        $tagIds = $post->tags()->orderBy('tags.id')->pluck('tags.id')->all();
        $draft = Post::where('is_published', false)->firstOrFail();
        $draft->tags()->detach();
        $this->seed(TagSeeder::class);
        $this->assertDatabaseCount('tags', 5);
        $this->assertSame($tagIds, $post->tags()->orderBy('tags.id')->pluck('tags.id')->all());
        $this->assertGreaterThanOrEqual(1, $draft->tags()->count());
        $this->assertLessThanOrEqual(3, $draft->tags()->count());
        $this->assertEqualsCanonicalizing(['Purok Liu', 'Urgent'], Post::where('is_pinned', true)->sole()->tags()->pluck('name')->all());
    }
}
