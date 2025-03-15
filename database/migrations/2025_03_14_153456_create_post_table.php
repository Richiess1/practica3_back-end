<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();                        // a. id
            $table->string('title');             // b. title
            $table->string('slug')->unique();    // c. slug
            $table->text('excerpt');             // d. excerpt
            $table->longText('content');         // e. content
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // f. user_id 
            $table->timestamps();                // g-h. created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
