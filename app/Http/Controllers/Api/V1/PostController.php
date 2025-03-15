<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
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

        return response()->json(
            $post->load([
                'categories:id,name',
                'user:id,name,email',
            ]),
            201
        );
    }

    public function index(Request $request)
    {
        $query = Auth::user()->posts();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $query->with('categories:id,name')->get();

        return response()->json($posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'categories' => $post->categories->pluck('name'),
                'user' => $post->user->name,
                'created_at' => $post->created_at,
            ];
        }));
    }
}
