<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le refus est expliqué à l'utilisateur (message de la policy), pas une page 403 muette.
        $response = Gate::inspect('propose', $this->route('listing'));
        if ($response->denied()) {
            abort(403, $response->message());
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mission = $this->route('listing')->isMission();

        return [
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'proposed_fcfa' => $mission ? ['required', 'integer', 'min:5000', 'max:50000000'] : ['prohibited'],
            'delivery_days' => $mission ? ['required', 'integer', 'min:1', 'max:180'] : ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // « 150 000 » ou « 150.000 » : on garde les chiffres.
        if ($this->filled('proposed_fcfa')) {
            $this->merge(['proposed_fcfa' => preg_replace('/\D/', '', (string) $this->input('proposed_fcfa'))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Écris un message à l\'auteur : ce que tu proposes et pourquoi toi.',
            'message.min' => 'Ton message est un peu court : dis ce que tu as déjà fait de proche.',
            'proposed_fcfa.required' => 'Indique ton prix en FCFA.',
            'proposed_fcfa.min' => 'Le prix doit être d\'au moins 5 000 FCFA.',
            'delivery_days.required' => 'Indique en combien de jours tu peux livrer.',
            'delivery_days.max' => 'Le délai doit être de 180 jours au plus.',
            'delivery_days.min' => 'Le délai doit être d\'au moins 1 jour.',
        ];
    }
}
