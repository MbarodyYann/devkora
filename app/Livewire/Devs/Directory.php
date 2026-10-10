<?php

namespace App\Livewire\Devs;

use App\Enums\Availability;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Annuaire des devs (/devs) : cartes, filtres par compétence, pays, langue et disponibilité, recherche par nom.
 */
#[Title('Les devs')]
#[Layout('components.layouts.app', ['pageHero' => [
    // Trait d'union insécable (U+2011) : « peut‑être » ne se coupe pas en fin de ligne.
    'start' => 'Le bon dev est peut‑être',
    'end' => 'dans ta ville.',
    'subtitle' => 'Filtre par compétence, pays et langue : les cauris montrent qui aide la communauté et qui a déjà livré.',
]])]
class Directory extends Component
{
    use WithPagination;

    public const PER_PAGE = 12;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'competence', except: '')]
    public string $skill = '';

    #[Url(as: 'pays', except: '')]
    public string $country = '';

    #[Url(as: 'langue', except: '')]
    public string $language = '';

    #[Url(as: 'dispo', except: '')]
    public string $availability = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'skill', 'country', 'language', 'availability');
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return collect([$this->q, $this->skill, $this->country, $this->language, $this->availability])->filter()->isNotEmpty();
    }

    /**
     * Pays où vivent des devs inscrits, dans l'ordre de config/countries.php.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function countryOptions(): array
    {
        return array_intersect_key(Countries::all(), array_flip(Profile::query()->distinct()->pluck('country')->all()));
    }

    /**
     * Langues parlées par au moins un membre, dans l'ordre de config/languages.php.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function languageOptions(): array
    {
        $spoken = Profile::query()->pluck('languages')->flatten()->unique()->all();

        return collect(config('languages'))->only($spoken)->map(fn (string $label) => Str::ucfirst($label))->all();
    }

    /**
     * Compétences d'au moins un membre, par ordre alphabétique.
     *
     * @return Collection<int, Skill>
     */
    #[Computed]
    public function skillOptions(): Collection
    {
        return Skill::query()->whereHas('users')->orderBy('name')->get(['name', 'slug']);
    }

    #[Computed]
    public function devs(): LengthAwarePaginator
    {
        $search = Str::squish($this->q);

        return User::query()
            ->whereHas('profile', fn (Builder $query) => $query
                ->when(array_key_exists($this->country, Countries::all()), fn (Builder $q) => $q->where('country', $this->country))
                ->when(Availability::tryFrom($this->availability), fn (Builder $q, Availability $availability) => $q->where('availability', $availability))
                ->when(array_key_exists($this->language, config('languages')), fn (Builder $q) => $q->whereJsonContains('languages', $this->language)))
            ->when($this->skill !== '', fn (Builder $query) => $query->whereHas('skills', fn (Builder $q) => $q->where('slug', $this->skill)))
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->with(['profile', 'skills'])
            ->orderByDesc('reputation')
            ->orderByRaw('last_seen_at desc nulls last')
            ->orderBy('name')
            ->paginate(self::PER_PAGE);
    }

    public function paginationView(): string
    {
        return 'components.pagination';
    }

    public function render(): View
    {
        return view('livewire.devs.directory', [
            // Chercher un dev indisponible n'a pas d'intérêt : le filtre propose les deux disponibilités utiles.
            'availabilities' => [Availability::Collab->value => Availability::Collab->shortLabel(), Availability::Missions->value => Availability::Missions->shortLabel()],
        ]);
    }
}
