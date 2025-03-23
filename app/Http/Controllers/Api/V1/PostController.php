<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostSummaryResource;


class PostController extends Controller
{
    
    // método para devolver un solo post al crearlo
    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string',
            'content' => 'required|string',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
        ]);

        $post = Auth::user()->posts()->create([
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
        ]);

        $post->categories()->attach($validated['categories']);

        return new PostResource($post);
    }

    public function index(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = Auth::user();

        // cargar relaciones con solo los campos necesarios
        $query = $user->posts()->with(['categories:id,name', 'user:id,name']);

        // filtrar por búsqueda si se proporciona
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // fbtener posts paginados
        $posts = $query->get();

        // retornar colección usando PostSummaryResource
        return PostSummaryResource::collection($posts);
    }

}
