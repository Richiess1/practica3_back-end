<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    // Esta prueba verifica que un usuario autenticado pueda crear un post correctamente
    public function test_user_can_create_post(): void
    {
        // Crear un usuario y dos categorías para asignar al post
        $user = User::factory()->create();
        $categories = Category::factory(2)->create();

        // Realizar una solicitud POST para crear un nuevo post
        $response = $this->actingAs($user)  // Actuar como el usuario creado
            ->postJson('/api/v1/posts', [  // Realizar solicitud POST a la API
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'categories' => $categories->pluck('id')->toArray(),  // Asignar categorías al post
            ]);

        // Verificar que la respuesta sea 201 (Creado) y que tenga la estructura adecuada
        $response->assertCreated()  
            ->assertJsonStructure([  // Verificar que la respuesta contenga todos los campos necesarios
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

        // Verificar que el post se ha guardado correctamente en la base de datos
        $this->assertDatabaseHas('posts', [
            'title' => 'Mi nueva publicación',
            'slug' => 'mi-nueva-publicacion',
            'user_id' => $user->id,
        ]);
    }

    // Esta prueba verifica que un usuario no pueda crear un post sin los campos requeridos
    public function test_user_cannot_create_post_without_required_fields(): void
    {
        // Crear un usuario
        $user = User::factory()->create();

        // Enviar una solicitud sin ningún dato
        $response = $this->actingAs($user)
            ->postJson('/api/v1/posts', []);  // Enviar solicitud con los campos vacíos

        // Verificar que la respuesta sea un error de validación (422)
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'excerpt', 'content', 'categories']);  // Comprobar que los campos requeridos son validados
    }

    // Esta prueba verifica que un usuario pueda crear un post incluso con un título duplicado
    public function test_user_can_create_post_with_duplicate_title(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Crear el primer post con un título específico
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ]);

        // Crear el segundo post con el mismo título
        $response = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',  // Título duplicado
                'excerpt' => 'Another excerpt',
                'content' => 'Another content',
                'categories' => [$category->id],
            ]);

        // Verificar que el segundo post también se crea correctamente
        $response->assertCreated();
        
        // Verificar que el slug del segundo post sea único (se agrega un "-1" al slug)
        $this->assertDatabaseHas('posts', [
            'title' => 'Mi nueva publicación',
            'slug' => 'mi-nueva-publicacion-1',
        ]);
    }

    // Esta prueba verifica que un usuario no autenticado no pueda crear un post
    public function test_unauthenticated_user_cannot_create_post(): void
    {
        // Intentar crear un post sin estar autenticado
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Mi nueva publicación',
            'excerpt' => 'Lorem ipsum sit amet',
            'content' => 'Lorem ipsum dolor sit amet.',
            'categories' => [1],
        ]);

        // Verificar que el sistema devuelva un error de "No autorizado"
        $response->assertUnauthorized();
    }

    // Esta prueba verifica que un usuario autenticado pueda listar sus propios posts
    public function test_user_can_list_their_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'demo']);

        // Crear un post para el usuario
        $post = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Mi nueva publicación',
                'excerpt' => 'Lorem ipsum sit amet',
                'content' => 'Lorem ipsum dolor sit amet.',
                'categories' => [$category->id],
            ]);

        // Realizar una solicitud GET para listar los posts del usuario
        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts');  // Solicitar los posts de la API

        // Verificar que la respuesta sea exitosa (200 OK)
        $response->assertOk()
            ->assertJsonStructure([  // Verificar que la estructura JSON de la respuesta sea correcta
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
            ->assertJsonCount(1)  // Verificar que haya solo un post
            ->assertJsonFragment([  // Verificar que el contenido del post esté presente en la respuesta
                'title' => 'Mi nueva publicación',
                'categories' => ['demo'],
            ]);
    }

    // Esta prueba verifica que un usuario pueda filtrar sus posts mediante un parámetro de búsqueda
    public function test_user_can_filter_their_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Crear un post que debería coincidir con el filtro de búsqueda
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post about Laravel',
                'excerpt' => 'Lorem ipsum',
                'content' => 'Content about Laravel framework',
                'categories' => [$category->id],
            ]);

        // Crear otro post que no debería coincidir con el filtro
        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Post about PHP',
                'excerpt' => 'Lorem ipsum',
                'content' => 'General PHP content',
                'categories' => [$category->id],
            ]);

        // Realizar una solicitud GET con el parámetro de búsqueda "Laravel"
        $response = $this->actingAs($user)
            ->getJson('/api/v1/posts?search=Laravel');  // Filtrar posts que contengan "Laravel" en el título

        // Verificar que la respuesta sea exitosa (200 OK) y que solo un post coincida con el filtro
        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([  // Verificar que el post "Laravel" esté presente en la respuesta
                'title' => 'Post about Laravel',
            ]);
    }

    // Esta prueba verifica que un usuario no autenticado no pueda listar los posts
    public function test_unauthenticated_user_cannot_list_posts(): void
    {
        // Intentar obtener los posts sin estar autenticado
        $response = $this->getJson('/api/v1/posts');  // Realizar una solicitud GET sin autenticación

        // Verificar que el sistema devuelva un error de "No autorizado"
        $response->assertUnauthorized();
    }
}
