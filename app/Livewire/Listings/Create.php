<?php

namespace App\Livewire\Listings;

use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\Skill;
use App\Services\BudgetEstimator;
use App\Services\ListingService;
use App\Support\Countries;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Publier une annonce (/annonces/publier) : d'abord le type (11), puis les champs adaptés.
 * Pour une mission, l'estimateur donne le budget médian des missions comparables.
 */
#[Title('Publier une annonce')]
#[Layout('components.layouts.app', ['noindex' => true])]
class Create extends Component
{
    public const MAX_SKILLS = 6;

    #[Url(except: '')]
    public string $type = '';

    public string $title = '';

    public string $description = '';

    public string $skills = '';

    public string $country = '';

    public bool $remote = false;

    public string $deadline = '';

    public string $rolesWanted = '';

    public string $hoursPerWeek = '';

    public string $budget = '';

    public string $deliveryDays = '';

    public function mount(): void
    {
        Gate::authorize('create', Listing::class);
        $this->country = auth()->user()->profile->country ?? '';
    }

    #[Computed]
    public function listingType(): ?ListingType
    {
        return ListingType::tryFrom($this->type);
    }

    /**
     * @return array{median: int, count: int, label: string}|null
     */
    #[Computed]
    public function estimate(): ?array
    {
        return $this->listingType === ListingType::Mission ? app(BudgetEstimator::class)->estimate(Skill::parseList($this->skills)) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $team = $this->listingType === ListingType::Team;

        return [
            'type' => ['required', Rule::enum(ListingType::class)],
            'title' => ['required', 'string', 'min:15', 'max:150'],
            'description' => ['required', 'string', 'min:80', 'max:10000'],
            'skills' => ['required', 'string', 'max:300'],
            'country' => $this->remote ? ['nullable'] : ['required', Rule::in(Countries::codes())],
            'deadline' => ['nullable', 'date', 'after:today'],
            'rolesWanted' => $team ? ['required', 'string', 'max:150'] : ['nullable'],
            'hoursPerWeek' => $team ? ['required', 'integer', 'between:1,60'] : ['nullable'],
            'budget' => $team ? ['nullable'] : ['required', 'integer', 'min:5000', 'max:50000000'],
            'deliveryDays' => $team ? ['nullable'] : ['required', 'integer', 'between:1,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title.required' => 'Donne un titre à ton annonce.',
            'title.min' => 'Ton titre est un peu court : dis ce que tu cherches en une phrase.',
            'description.required' => 'Décris ton projet ou ta mission.',
            'description.min' => 'Ajoute un peu de détail : le contexte, ce qui est attendu, ce qui existe déjà.',
            'skills.required' => 'Ajoute au moins une compétence, par exemple Laravel.',
            'country.required' => 'Choisis un pays, ou coche « Télétravail ».',
            'country.in' => 'Choisis un pays dans la liste, ou coche « Télétravail ».',
            'deadline.after' => 'La date limite doit être dans le futur.',
            'rolesWanted.required' => 'Indique les rôles recherchés, par exemple « Dev back-end, designer ».',
            'hoursPerWeek.required' => 'Indique l\'engagement attendu, en heures par semaine.',
            'hoursPerWeek.between' => 'L\'engagement doit être entre 1 et 60 heures par semaine.',
            'budget.required' => 'Indique ton budget en FCFA.',
            'budget.integer' => 'Le budget s\'écrit en chiffres, par exemple 150000.',
            'budget.min' => 'Le budget doit être d\'au moins 5 000 FCFA.',
            'deliveryDays.required' => 'Indique le délai souhaité, en jours.',
            'deliveryDays.between' => 'Le délai doit être entre 1 et 180 jours.',
        ];
    }

    public function updatedBudget(): void
    {
        $this->budget = preg_replace('/\D/', '', $this->budget);
    }

    public function publish(ListingService $listings)
    {
        Gate::authorize('create', Listing::class);
        $this->budget = preg_replace('/\D/', '', $this->budget);
        $this->validate();

        $skills = Skill::parseList($this->skills);
        if ($skills === []) {
            $this->addError('skills', 'Ajoute au moins une compétence, par exemple Laravel.');

            return null;
        }
        if (count($skills) > self::MAX_SKILLS) {
            $this->addError('skills', 'Garde '.self::MAX_SKILLS.' compétences au plus : les plus utiles.');

            return null;
        }

        if (! RateLimiter::attempt('listings:'.auth()->id(), 10, fn () => true, 3600)) {
            $this->addError('title', 'Tu as publié plusieurs annonces en peu de temps : réessaie dans une heure.');

            return null;
        }

        $team = $this->listingType === ListingType::Team;
        $listing = $listings->publish(auth()->user(), $this->listingType, [
            'title' => trim($this->title),
            'description' => $this->description,
            'country' => $this->remote ? null : $this->country,
            'deadline' => $this->deadline ?: null,
            'roles_wanted' => $team ? trim($this->rolesWanted) : null,
            'hours_per_week' => $team ? (int) $this->hoursPerWeek : null,
            'budget_fcfa' => $team ? null : (int) $this->budget,
            'delivery_days' => $team ? null : (int) $this->deliveryDays,
        ], $skills);

        session()->flash('status', $team
            ? 'Annonce publiée. Les devs intéressés t\'écrivent ici : tu choisis qui rejoint l\'équipe.'
            : 'Mission publiée. Les propositions des devs arrivent ici : tu compares, puis tu retiens ton dev.');

        return $this->redirectRoute('listings.show', $listing);
    }

    public function render(): View
    {
        return view('livewire.listings.create', [
            'countries' => Countries::grouped(),
            'suggestedSkills' => Skill::query()->orderByDesc('is_local')->orderBy('name')->pluck('name'),
        ]);
    }
}
