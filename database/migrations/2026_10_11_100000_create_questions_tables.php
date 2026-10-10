<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            // HTML rendu une fois à l'enregistrement (Markdown sécurisé et code coloré).
            $table->text('body_html');
            $table->string('excerpt', 300);
            $table->integer('score')->default(0);
            $table->unsignedInteger('answers_count')->default(0);
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->text('body_html');
            $table->integer('score')->default(0);
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('accepted_answer_id')->nullable()->after('excerpt')->constrained('answers')->nullOnDelete();
        });

        Schema::create('question_skill', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['question_id', 'skill_id']);
            $table->index('skill_id');
        });

        // Recherche plein texte en français (websearch_to_tsquery), titre plus important que le corps.
        DB::statement("create index questions_search_index on questions using gin ((setweight(to_tsvector('french', title), 'A') || setweight(to_tsvector('french', body), 'B')))");
    }

    public function down(): void
    {
        Schema::dropIfExists('question_skill');
        Schema::table('questions', fn (Blueprint $table) => $table->dropConstrainedForeignId('accepted_answer_id'));
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
    }
};
