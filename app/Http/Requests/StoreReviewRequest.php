<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('listing'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'communication' => ['required', 'integer', 'between:1,5'],
            'quality' => ['required', 'integer', 'between:1,5'],
            'timeliness' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Donne une note globale, de 1 à 5 étoiles.',
            'communication.required' => 'Note la communication, de 1 à 5.',
            'quality.required' => 'Note la qualité du travail, de 1 à 5.',
            'timeliness.required' => 'Note le respect du délai, de 1 à 5.',
        ];
    }
}
