<?php

namespace App\Http\Requests;

use App\Models\Question;
use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public const MAX_TAGS = 5;

    public function authorize(): bool
    {
        return $this->user()->can('create', Question::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:15', 'max:150'],
            'body' => ['required', 'string', 'min:30', 'max:20000'],
            'tags' => ['required', 'string', 'max:300'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tags = $this->tagNames();

                if ($this->filled('tags') && $tags === []) {
                    $validator->errors()->add('tags', 'Ajoute au moins un tag, par exemple Laravel.');
                }
                if (count($tags) > self::MAX_TAGS) {
                    $validator->errors()->add('tags', 'Garde '.self::MAX_TAGS.' tags au plus : les plus précis.');
                }
                if (collect($tags)->contains(fn (string $tag) => mb_strlen($tag) > 40)) {
                    $validator->errors()->add('tags', 'Chaque tag doit faire 40 caractères au plus.');
                }
            },
        ];
    }

    /**
     * @return list<string>
     */
    public function tagNames(): array
    {
        return Skill::parseList($this->input('tags'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Donne un titre à ta question.',
            'title.min' => 'Ton titre est un peu court : résume ton problème en une phrase.',
            'body.required' => 'Explique ton problème : ce que tu as essayé, ce qui se passe.',
            'body.min' => 'Ajoute un peu de détail : ce que tu as essayé, le message d\'erreur.',
            'tags.required' => 'Ajoute au moins un tag, par exemple Laravel.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'titre', 'body' => 'détail', 'tags' => 'tags'];
    }
}
