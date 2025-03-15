<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it generates slug from title', function () {
    // Test basic slug generation
    $slug = Post::generateUniqueSlug('My Test Post');
    expect($slug)->toBe('my-test-post');
});

test('it generates unique slug when duplicate exists', function () {
    // Create a post with a known title/slug
    $user = User::factory()->create();

    // Create the first post using the relationship
    $post = $user->posts()->create([
        'title' => 'My Test Post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
    ]);

    // The first post should have the basic slug
    expect($post->slug)->toBe('my-test-post');

    // Now generate a slug for the same title
    $newSlug = Post::generateUniqueSlug('My Test Post');

    // It should append -1 to make it unique
    expect($newSlug)->toBe('my-test-post-1');
});

test('it handles special characters in title', function () {
    // Test with special characters
    $slug = Post::generateUniqueSlug('My Test Post: With "Special" Characters!');
    expect($slug)->toBe('my-test-post-with-special-characters');
});

test('it creates slug automatically when creating post', function () {
    $user = User::factory()->create();

    // Use the relationship to create the post
    $post = $user->posts()->create([
        'title' => 'Automatic Slug Test',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
    ]);

    // Verify the slug was automatically generated
    expect($post->slug)->toBe('automatic-slug-test');
});
