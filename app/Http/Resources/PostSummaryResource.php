<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'categories' => $this->categories->pluck('name'), // Solo nombres
            'user' => $this->user->name, // Solo nombre
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
