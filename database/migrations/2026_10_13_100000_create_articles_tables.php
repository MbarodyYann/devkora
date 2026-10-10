<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category')->index(); // tutorial, experience, best_practice, project_story
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 300);
            $table->text('body');
            $table->text('body_html');
            $table->unsignedSmallInteger('reading_minutes');
            $table->unsignedInteger('score')->default(0); // votes « Utile »
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('article_skill', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['article_id', 'skill_id']);
            $table->index('skill_id');
        });

        // Même recherche que les questions : français, sans accents, titre plus important.
        DB::statement("create index articles_search_index on articles using gin ((setweight(to_tsvector('french', f_unaccent(title)), 'A') || setweight(to_tsvector('french', f_unaccent(body)), 'B')))");
    }

    public function down(): void
    {
        Schema::dropIfExists('article_skill');
        Schema::dropIfExists('articles');
    }
};
