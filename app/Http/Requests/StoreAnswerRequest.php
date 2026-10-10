<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('answer', $this->route('question'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:20', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Écris ta réponse avant de la publier.',
            'body.min' => 'Ta réponse est un peu courte : explique le pourquoi, pas seulement le quoi.',
        ];
    }
}
