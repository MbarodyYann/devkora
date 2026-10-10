<?php

namespace App\Livewire\Listings;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\Skill;
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
 * Annonces entre devs (/annonces) : équipes et missions, par compétence, pays ou télétravail, recherche.
 */
#[Title('Annonces')]
#[Layout('components.layouts.app', ['pageHero' => [
    'start' => 'Construis avec les tiens,',
    'end' => 'd\'un pays à l\'autre.',
    'subtitle' => 'Trouve des coéquipiers pour ton projet ou confie une mission à un dev de confiance, payée en FCFA par Mobile Money.',
    'action' => ['label' => 'Publier une annonce', 'route' => 'listings.create'],
]])]
class Index extends Component
{
    use WithPagination;

    public const PER_PAGE = 12;

    /** Valeur du filtre de lieu pour les annonces en télétravail. */
    public const REMOTE = 'teletravail';

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(as: 'competence', except: '')]
    public string $skill = '';

    #[Url(as: 'lieu', except: '')]
    public string $place = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'type', 'skill', 'place');
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return collect([$this->q, $this->type, $this->skill, $this->place])->filter()->isNotEmpty();
    }

    /**
     * @return Collection<int, Skill>
     */
    #[Computed]
    public function skillOptions(): Collection
    {
        return Skill::query()->whereHas('listings', fn (Builder $q) => $q->whereNull('hidden_at'))
            ->orderByDesc('is_local')->orderBy('name')->get(['name', 'slug']);
    }

    /**
     * Télétravail, puis les pays des annonces, dans l'ordre de config/countries.php.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function placeOptions(): array
    {
        $present = Listing::query()->visible()->whereNotNull('country')->distinct()->pluck('country')->all();

        return [self::REMOTE => 'Télétravail'] + array_intersect_key(Countries::all(), array_flip($present));
    }

    #[Computed]
    public function listings(): LengthAwarePaginator
    {
        $search = Str::squish($this->q);

        return Listing::query()
            ->visible()
            ->when(ListingType::tryFrom($this->type), fn (Builder $q, ListingType $type) => $q->where('type', $type))
            ->when($this->skill !== '', fn (Builder $q) => $q->whereHas('skills', fn (Builder $s) => $s->where('slug', $this->skill)))
            ->when($this->place === self::REMOTE, fn (Builder $q) => $q->whereNull('country'))
            ->when(array_key_exists($this->place, Countries::all()), fn (Builder $q) => $q->where('country', $this->place))
            ->when($search !== '', fn (Builder $q) => $q->fullTextSearch($search))
            ->with(['user.profile', 'skills', 'acceptedProposals.user.profile'])
            ->withCount('proposals')
            // Les annonces ouvertes d'abord, puis les plus récentes.
            ->orderByRaw('status = ? desc', [ListingStatus::Open->value])
            ->latest()
            ->paginate(self::PER_PAGE);
    }

    public function paginationView(): string
    {
        return 'components.pagination';
    }

    public function render(): View
    {
        return view('livewire.listings.index', [
            'types' => ['' => 'Tout', ListingType::Team->value => 'Cherche coéquipiers', ListingType::Mission->value => 'Missions entre devs'],
        ]);
    }
}
