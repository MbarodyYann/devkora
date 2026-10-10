<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            // Compétence propre au contexte africain : Wave, Orange Money, USSD, 3G…
            $table->boolean('is_local')->default(false);
            $table->timestamps();
        });

        Schema::create('skill_user', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Ordre choisi par le membre : sa compétence principale en premier.
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['skill_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_user');
        Schema::dropIfExists('skills');
    }
};
