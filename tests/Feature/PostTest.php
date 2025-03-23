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

        $response->assertCreated()
            ->assertJsonStructure([
                'data'=>[
                'id', 'title', 'slug', 'excerpt', 'content',
                'categories' => [['id', 'name']],
                'user' => ['id', 'name', 'email'],
                'created_at', 'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Mi nueva publicación',
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

    public function test_user_can_create_post_with_duplicate_slug(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Crear primer post
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ]);

        // Crear segundo post con el mismo título
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
        ]);
    }

    public function test_unauthenticated_user_cannot_access_post_endpoints(): void
    {
        // Crear post sin autenticación
        $createResponse = $this->postJson('/api/v1/posts', [
            'title' => 'Mi nueva publicación',
            'excerpt' => 'Lorem ipsum sit amet',
            'content' => 'Lorem ipsum dolor sit amet.',
            'categories' => [1],
        ]);
        $createResponse->assertUnauthorized();

        // Listar posts sin autenticación
        $listResponse = $this->getJson('/api/v1/posts');
        $listResponse->assertUnauthorized();
    }

    public function test_user_can_list_their_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post 1',
                'excerpt' => 'Lorem ipsum',
                'content' => 'Contenido de prueba',
                'categories' => [$category->id],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Post 1']);
    }

    public function test_user_can_filter_their_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post about Laravel',
                'excerpt' => 'Lorem ipsum',
                'content' => 'Content about Laravel',
                'categories' => [$category->id],
            ]);

        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post about PHP',
                'excerpt' => 'Lorem ipsum',
                'content' => 'Content about PHP',
                'categories' => [$category->id],
            ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts?search=Laravel');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Post about Laravel']);
    }
}
