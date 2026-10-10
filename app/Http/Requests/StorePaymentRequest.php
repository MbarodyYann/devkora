<?php

namespace App\Http\Requests;

use App\Enums\PaymentProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hire', $this->route('listing'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(PaymentProvider::class)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{8,18}$/'],
        ];
    }

    /** Numéro au format international, chiffres seulement. */
    public function phone(): string
    {
        return '+'.ltrim(preg_replace('/\D/', '', (string) $this->validated('phone')), '0');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required' => 'Choisis ton opérateur Mobile Money.',
            'provider.enum' => 'Choisis ton opérateur Mobile Money.',
            'phone.required' => 'Indique ton numéro Mobile Money.',
            'phone.regex' => 'Indique ton numéro au format international, par exemple +225 07 07 07 07 07.',
        ];
    }
}
