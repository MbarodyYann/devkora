<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Services\MarkdownRenderer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = 'Essaie plutôt ceci : '.fake()->sentence(15);

        return [
            'question_id' => Question::factory(),
            'user_id' => User::factory()->withProfile(),
            'body' => $body,
            'body_html' => app(MarkdownRenderer::class)->toHtml($body),
        ];
    }
}
