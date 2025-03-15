<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PostResource;

class PostController extends Controller
{
    // Método para devolver un solo post al crearlo
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

    public function index(Request $request){
        $query = Auth::user()->posts()->with('categories', 'user');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        }

        return PostResource::collection($query->get());
    }
}
