<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Skill;
use App\Models\User;
use App\Support\French;
use App\Support\Text;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    public function __construct(private readonly MarkdownRenderer $markdown) {}

    /**
     * @param  list<string>  $tags  noms des compétences, dans l'ordre choisi
     */
    public function publish(User $author, string $title, string $body, array $tags): Question
    {
        return DB::transaction(function () use ($author, $title, $body, $tags) {
            $question = new Question(['title' => French::typography($title), 'body' => $body]);
            $question->forceFill([
                'user_id' => $author->id,
                'body_html' => $this->markdown->toHtml($body),
                'excerpt' => Text::excerpt($body),
            ])->save();

            $question->skills()->sync(Skill::idsFor($tags));

            return $question;
        });
    }
}
