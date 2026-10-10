<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Models\Skill;
use App\Support\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public const MAX_SKILLS = 12;

    public const MAX_LANGUAGES = 6;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user')->profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'headline' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'country' => ['required', 'string', Rule::in(Countries::codes())],
            'city' => ['required', 'string', 'max:80'],
            'languages' => ['required', 'array', 'min:1', 'max:'.self::MAX_LANGUAGES],
            'languages.*' => ['string', Rule::in(array_keys(config('languages')))],
            'availability' => ['required', Rule::enum(Availability::class)],
            'github_url' => ['nullable', 'url:https', 'max:255', 'starts_with:https://github.com/'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=64,min_height=64,max_width=6000,max_height=6000'],
            'skills' => ['nullable', 'string', 'max:600'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $skills = $this->skillNames();

                if (count($skills) > self::MAX_SKILLS) {
                    $validator->errors()->add('skills', 'Garde tes '.self::MAX_SKILLS.' compétences principales.');
                }

                if (collect($skills)->contains(fn (string $skill) => mb_strlen($skill) > 40)) {
                    $validator->errors()->add('skills', 'Chaque compétence doit faire 40 caractères au plus.');
                }
            },
        ];
    }

    /**
     * Compétences saisies sous la forme « Laravel, Flutter, Wave », dédoublonnées.
     *
     * @return list<string>
     */
    public function skillNames(): array
    {
        return collect(explode(',', (string) $this->input('skills')))
            ->map(fn (string $skill) => Str::squish($skill))
            ->filter()
            ->unique(fn (string $skill) => Skill::slugFor($skill))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country.in' => 'Choisis ton pays dans la liste.',
            'languages.required' => 'Choisis au moins une langue.',
            'languages.max' => 'Choisis '.self::MAX_LANGUAGES.' langues au plus.',
            'github_url.starts_with' => 'Le lien GitHub doit commencer par https://github.com/.',
            'github_url.url' => 'Le lien GitHub doit commencer par https://github.com/.',
            'portfolio_url.url' => 'Colle l\'adresse complète de ton portfolio, avec https://.',
            'avatar.max' => 'La photo doit peser 4 Mo au plus.',
            'avatar.image' => 'Choisis une photo au format JPG, PNG ou WebP.',
            'avatar.mimes' => 'Choisis une photo au format JPG, PNG ou WebP.',
            'avatar.dimensions' => 'La photo doit mesurer au moins 64 × 64 pixels.',
        ];
    }
}
