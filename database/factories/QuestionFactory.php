<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use App\Services\MarkdownRenderer;
use App\Support\Text;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = 'Je cherche comment '.fake()->sentence(12)."\n\n```php\necho 'test';\n```";

        return [
            'user_id' => User::factory()->withProfile(),
            'title' => 'Comment '.fake()->sentence(6),
            'body' => $body,
            'body_html' => app(MarkdownRenderer::class)->toHtml($body),
            'excerpt' => Text::excerpt($body),
        ];
    }
}
