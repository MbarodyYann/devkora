<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // meetup, workshop, hackathon
            $table->string('title');
            $table->text('description');
            $table->text('description_html');
            $table->string('excerpt', 300);
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at');
            $table->boolean('online')->default(false);
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('registration_url')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
        });

        Schema::create('event_user', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['event_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('listings', function (Blueprint $table) {
            // Annonce « Cherche coéquipiers » liée à un hackathon.
            $table->foreignId('event_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->dropConstrainedForeignId('event_id'));
        Schema::dropIfExists('event_user');
        Schema::dropIfExists('events');
    }
};
