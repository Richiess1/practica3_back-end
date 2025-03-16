<?php

namespace App\Utils;

use Illuminate\Support\Str;
use App\Models\Post;

class SlugGenerator
{
    public static function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        // Verifica si el slug ya existe en la BD
        while (Post::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
