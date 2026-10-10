<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // team, mission
            $table->string('title');
            $table->text('description');
            $table->text('description_html');
            $table->string('excerpt', 300);
            $table->string('roles_wanted')->nullable();               // team
            $table->unsignedSmallInteger('hours_per_week')->nullable(); // team
            $table->unsignedInteger('budget_fcfa')->nullable();       // mission
            $table->unsignedSmallInteger('delivery_days')->nullable(); // mission
            $table->string('country', 2)->nullable();                 // null = télétravail
            $table->date('deadline')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status']);
            $table->index('created_at');
        });

        Schema::create('listing_skill', function (Blueprint $table) {
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['listing_id', 'skill_id']);
            $table->index('skill_id');
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->unsignedInteger('proposed_fcfa')->nullable();     // mission
            $table->unsignedSmallInteger('delivery_days')->nullable(); // mission
            $table->string('status')->default('pending')->index();   // pending, accepted, declined
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['listing_id', 'user_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // wave, orange_money, free_money, mtn_momo, moov_money
            $table->string('phone');
            $table->unsignedInteger('amount_fcfa');
            $table->string('status'); // held, released, refunded
            $table->string('reference')->unique(); // DK-XXXXXX
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('quality');
            $table->unsignedTinyInteger('timeliness');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index('reviewee_id');
        });

        DB::statement("create index listings_search_index on listings using gin ((setweight(to_tsvector('french', f_unaccent(title)), 'A') || setweight(to_tsvector('french', f_unaccent(description)), 'B')))");
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('listing_skill');
        Schema::dropIfExists('listings');
    }
};
