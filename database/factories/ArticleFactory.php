<?php

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleService;
use App\Services\MarkdownRenderer;
use App\Support\Text;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Guide pratique : '.fake()->unique()->sentence(5);
        $body = "## Le contexte\n\n".fake()->paragraphs(4, true)."\n\n```bash\nphp artisan migrate\n```";

        return [
            'user_id' => User::factory()->withProfile(),
            'category' => ArticleCategory::Tutorial,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'body' => $body,
            'body_html' => app(MarkdownRenderer::class)->toHtml($body),
            'excerpt' => Text::excerpt($body, 240),
            'reading_minutes' => ArticleService::readingMinutes($body),
        ];
    }
}
