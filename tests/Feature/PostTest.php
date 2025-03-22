<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();
        $categories = Category::factory(2)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'categories' => $categories->pluck('id')->toArray(),
            ]);

        dd($response->json()); // Para depuración

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Mi nueva publicación',
            'slug' => 'mi-nueva-publicacion',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_post_without_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/posts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'excerpt', 'content', 'categories']);
    }

    public function test_user_can_create_post_with_duplicate_title(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create first post
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ]);

        // Create second post with same title
        $response = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Another excerpt',
                'content' => 'Another content',
                'categories' => [$category->id],
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('posts', [
            'title' => 'Mi nueva publicación',
            'slug' => 'mi-nueva-publicacion-1',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Mi nueva publicación',
            'excerpt' => 'Lorem ipsum sit amet',
            'content' => 'Lorem ipsum dolor sit amet.',
            'categories' => [1],
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_list_their_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'demo']);

        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts');

        dd($response->json()); // Para depuración

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'categories',
                    'user',
                    'created_at',
                ],
            ])
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'title' => 'Mi nueva publicación',
                'categories' => ['demo'],
            ]);
    }

    public function test_user_can_filter_their_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a post that should match the filter
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post about Laravel',
                'excerpt' => 'Lorem ipsum',
                'content' => 'Content about Laravel framework',
                'categories' => [$category->id],
            ]);

        // Create a post that should not match the filter
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post about PHP',
                'excerpt' => 'Lorem ipsum',
                'content' => 'General PHP content',
                'categories' => [$category->id],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts?search=Laravel');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'title' => 'Post about Laravel',
            ]);
    }

    public function test_unauthenticated_user_cannot_list_posts(): void
    {
        $response = $this->getJson('/api/v1/posts');

        $response->assertUnauthorized();
    }

    public function test_user_can_show_single_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ])
            ->json('data');

        $response = $this->actingAs($user)
            ->getJson("/api/v1/posts/{$post['id']}");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'categories',
                'user',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'title' => 'Mi nueva publicación',
            ]);
    }

    public function test_user_cannot_show_non_existing_post(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts/999');

        $response->assertNotFound();
    }

    public function test_user_can_update_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ])
            ->json('data');

        $response = $this->actingAs($user)
            ->putJson("/api/v1/posts/{$post['id']}", [
                'title' => 'Mi publicación actualizada',
                'excerpt' => 'Nuevo extracto',
                'content' => 'Contenido actualizado',
                'categories' => [$category->id],
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'title' => 'Mi publicación actualizada',
            ]);
    }

    public function test_user_can_delete_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ])
            ->json('data');

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$post['id']}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('posts', ['id' => $post['id']]);
    }

    public function test_user_cannot_delete_other_users_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $post = $this->actingAs($otherUser)
            ->postJson('/api/v1/posts', [
                'title' => 'Post de otro usuario',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Contenido de otro usuario',
                'categories' => [$category->id],
            ])
            ->json('data');

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$post['id']}");

        $response->assertForbidden();
    }
}
