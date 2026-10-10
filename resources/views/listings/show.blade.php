@php
    use App\Enums\ListingStatus;
    use App\Enums\ProposalStatus;
    use App\Support\Copy;
    use App\Support\Money;

    $me = auth()->user();
    $mission = $listing->isMission();
    $status = $listing->status;
    $canPropose = $me && Gate::allows('propose', $listing);
    $retained = $listing->acceptedProposals->first();

    // Action principale de l'écran (barre collante sur mobile) : une seule, selon qui regarde.
    $primary = match (true) {
        $isAuthor && $mission && $status === ListingStatus::Open && $pendingCount > 0 => ['label' => 'Choisis ton dev ('.$pendingCount.')', 'href' => '#propositions'],
        $isAuthor && $mission && $status === ListingStatus::InProgress => ['label' => 'Confirmer la fin de la mission', 'href' => '#agir'],
        $isAuthor && Gate::allows('review', $listing) => ['label' => 'Laisser un avis', 'href' => '#avis'],
        $isAuthor && ! $mission && $status === ListingStatus::Open && $pendingCount > 0 => ['label' => 'Voir les demandes ('.$pendingCount.')', 'href' => '#demandes'],
        $canPropose => ['label' => $mission ? 'Proposer mon aide' : 'Rejoindre l\'équipe', 'href' => '#agir'],
        // Visiteur : la barre mène à la boîte d'action (le CTA d'inscription est déjà dans la navigation).
        ! $me && $status === ListingStatus::Open => ['label' => $mission ? 'Décroche cette mission' : 'Rejoins cette équipe', 'href' => '#agir'],
        default => null,
    };
@endphp

<x-layouts.app :title="$listing->title" :description="$listing->excerpt">
    <a href="{{ route('listings.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-left" class="size-4" /> Toutes les annonces
    </a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            {{-- L'annonce --}}
            <x-card>
                <div class="flex flex-wrap items-center gap-2">
                    <x-chip :tone="$mission ? 'info' : 'neutral'">{{ $listing->type->label() }}</x-chip>
                    <x-chip :tone="$status->tone()">{{ $status->label($listing->type) }}</x-chip>
                </div>
                <h1 class="mt-4 break-words text-[30px] leading-[1.15] sm:text-[40px]">{{ $listing->title }}</h1>
                <div class="mt-4">
                    <x-author :user="$listing->user" :date="$listing->created_at" verb="Publiée" />
                </div>
                <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <div class="flex items-center gap-1.5"><dt class="sr-only">Lieu</dt><x-icon name="map-pin" class="size-4 text-ink-muted" /><dd class="text-ink">{{ $listing->placeLabel() }}</dd></div>
                    @if ($mission)
                        <div class="flex items-center gap-1.5"><dt class="text-ink-muted">Délai souhaité</dt><dd class="font-medium text-ink">{{ $listing->delivery_days }} jours</dd></div>
                    @else
                        <div class="flex items-center gap-1.5"><dt class="text-ink-muted">Engagement</dt><dd class="font-medium text-ink">{{ $listing->hours_per_week }} h par semaine</dd></div>
                    @endif
                    @if ($listing->deadline)
                        <div class="flex items-center gap-1.5"><dt class="text-ink-muted">Date limite</dt><dd class="font-medium text-ink">{{ $listing->deadline->translatedFormat('j M Y') }}</dd></div>
                    @endif
                </dl>
                @unless ($mission)
                    <p class="mt-4 text-ink"><span class="font-semibold">Rôles recherchés :</span> {{ $listing->roles_wanted }}</p>
                @endunless
                <x-skills :skills="$listing->skills" class="mt-5" />
                <div class="prose-dk mt-6 border-t border-line pt-6">{!! $listing->description_html !!}</div>
            </x-card>

            {{-- Équipe : l'auteur et les membres acceptés --}}
            @unless ($mission)
                <x-card id="equipe" class="scroll-mt-24">
                    <h2 class="text-[28px] sm:text-[32px]">L'équipe</h2>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach (collect([$listing->user])->merge($listing->acceptedProposals->pluck('user')) as $member)
                            <li class="flex items-center gap-3 py-3">
                                <x-avatar :user="$member" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('devs.show', $member) }}" class="block truncate font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $member->name }}</a>
                                    <p class="truncate text-sm text-ink-muted">{{ $member->profile?->headline }} · {{ $member->profile?->city }} <span aria-hidden="true">{{ $member->profile?->flag() }}</span></p>
                                </div>
                                @if ($loop->first)
                                    <x-chip tone="neutral" class="shrink-0">Porteur du projet</x-chip>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-card>

                {{-- Demandes à traiter (auteur) --}}
                @if ($isAuthor && $status === ListingStatus::Open)
                    <x-card id="demandes" class="scroll-mt-24">
                        <h2 class="text-[28px] sm:text-[32px]">Demandes pour rejoindre l'équipe</h2>
                        @php($requests = $proposals->where('status', ProposalStatus::Pending))
                        @if ($requests->isEmpty())
                            <p class="mt-3 text-ink-muted">Pas de nouvelle demande. Partage ton annonce dans tes groupes de devs pour la faire connaître.</p>
                        @else
                            <ul class="mt-4 space-y-4">
                                @foreach ($requests as $proposal)
                                    <li class="rounded-2xl border border-line p-4">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :user="$proposal->user" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('devs.show', $proposal->user) }}" class="block truncate font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $proposal->user->name }}</a>
                                                <p class="truncate text-sm text-ink-muted">{{ $proposal->user->profile?->headline }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-3 whitespace-pre-line break-words text-ink-soft">{{ $proposal->message }}</p>
                                        <div class="mt-4 flex flex-wrap items-center gap-3">
                                            <form method="post" action="{{ route('proposals.accept', [$listing, $proposal]) }}">@csrf<x-button type="submit" size="sm"><x-icon name="check" class="size-4" /> Accepter dans l'équipe</x-button></form>
                                            <form method="post" action="{{ route('proposals.decline', [$listing, $proposal]) }}">@csrf<x-button type="submit" variant="ghost" size="sm">Refuser</x-button></form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-card>
                @endif
            @endunless

            {{-- Mission ouverte : l'auteur compare les propositions (11, « Choisis ton dev ») --}}
            @if ($isAuthor && $mission && $status === ListingStatus::Open)
                <section id="propositions" class="scroll-mt-24" aria-labelledby="propositions-titre">
                    <h2 id="propositions-titre" class="text-[28px] sm:text-[32px]">Choisis ton dev</h2>
                    <p class="mt-2 text-ink-soft">Quand tu retiens un dev, tu sécurises les fonds (simulation) et les autres propositions passent en « Non retenue ».</p>
                    @if ($proposals->isEmpty())
                        <p class="mt-4 text-ink-muted">Pas encore de proposition. Les devs qui ont ces compétences voient ta mission dans les annonces.</p>
                    @else
                        <ul class="mt-4 space-y-4" data-proposals>
                            @foreach ($proposals as $proposal)
                                @php($dev = $proposal->user)
                                <li>
                                    <x-card padding="p-5 sm:p-6">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <x-avatar :user="$dev" />
                                                <div class="min-w-0">
                                                    <a href="{{ route('devs.show', $dev) }}" class="block truncate font-serif text-xl font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $dev->name }}</a>
                                                    <p class="truncate text-sm text-ink-muted">{{ $dev->profile?->headline }}</p>
                                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                                                        <x-reputation :points="$dev->reputation" />
                                                        <x-level :user="$dev" />
                                                        @if ($proposal->status !== ProposalStatus::Pending)
                                                            <x-chip :tone="$proposal->status->tone()">{{ $proposal->status->label($listing->type) }}</x-chip>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <dl class="grid shrink-0 grid-cols-2 gap-x-6 text-sm sm:text-right">
                                                <div><dt class="text-ink-muted">Prix</dt><dd class="mt-0.5"><x-budget :amount="$proposal->proposed_fcfa" /></dd></div>
                                                <div><dt class="text-ink-muted">Délai</dt><dd class="mt-0.5 font-serif text-[28px] leading-none text-ink">{{ $proposal->delivery_days }} <span class="font-sans text-sm font-semibold text-ink-muted">j</span></dd></div>
                                            </dl>
                                        </div>
                                        <p class="mt-4 text-sm text-ink-muted">{{ collect([$dev->profile?->languagesLabel(), $dev->activityLabel()])->filter()->join(' · ') }}</p>
                                        <p class="mt-3 whitespace-pre-line break-words text-ink-soft">{{ $proposal->message }}</p>
                                        @if ($proposal->status === ProposalStatus::Pending)
                                            <div class="mt-5 border-t border-line pt-4">
                                                <x-button :href="route('payments.create', [$listing, $proposal])"><x-icon name="check" class="size-4" /> Retenir ce dev</x-button>
                                            </div>
                                        @endif
                                    </x-card>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            {{-- Mission en cours ou terminée : le dev retenu, et l'avis --}}
            @if ($mission && $retained && in_array($status, [ListingStatus::InProgress, ListingStatus::Completed, ListingStatus::Cancelled], true))
                <x-card>
                    <h2 class="text-[28px] sm:text-[32px]">Le dev retenu</h2>
                    <a href="{{ route('devs.show', $retained->user) }}" class="mt-4 flex min-w-0 items-center gap-3">
                        <x-avatar :user="$retained->user" />
                        <span class="min-w-0">
                            <span class="block truncate font-serif text-xl font-medium text-ink">{{ $retained->user->name }}</span>
                            <span class="block text-sm text-ink-muted">{{ Money::fcfa($retained->proposed_fcfa) }} · {{ $retained->delivery_days }} jours</span>
                        </span>
                    </a>
                </x-card>
            @endif

            @if ($mission && $status === ListingStatus::Completed)
                <x-card id="avis" class="scroll-mt-24">
                    @if ($listing->review)
                        <h2 class="text-[28px] sm:text-[32px]">L'avis</h2>
                        <x-review-detail :review="$listing->review" class="mt-4" />
                    @elseif (Gate::allows('review', $listing))
                        <h2 class="text-[28px] sm:text-[32px]">Note le travail {{ App\Support\French::de($retained->user->firstName()) }}</h2>
                        <p class="mt-2 text-ink-soft">Ton avis apparaît sur son profil et aide les autres devs à choisir.</p>
                        <form method="post" action="{{ route('reviews.store', $listing) }}" class="mt-5 space-y-5" novalidate>
                            @csrf
                            @foreach (['rating' => 'Note globale'] + App\Models\Review::CRITERIA as $field => $label)
                                <fieldset class="min-w-0">
                                    <legend class="text-sm font-medium text-ink">{{ $label }}</legend>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach (range(1, 5) as $value)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="{{ $field }}" value="{{ $value }}" @checked((int) old($field) === $value) class="peer sr-only">
                                                <span class="inline-flex size-11 items-center justify-center rounded-btn border border-line font-semibold text-ink transition peer-checked:border-secondary-400 peer-checked:bg-secondary-400 peer-checked:text-primary-950 peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600">{{ $value }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error($field)
                                        <p class="mt-1.5 flex items-start gap-1.5 text-sm font-medium text-secondary-800 dark:text-secondary-300"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $message }}</p>
                                    @enderror
                                </fieldset>
                            @endforeach
                            <x-field label="Commentaire" name="comment" optional>
                                <x-textarea name="comment" rows="4" maxlength="1000" />
                            </x-field>
                            <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-end">
                                <p class="text-sm text-ink-muted sm:mr-auto">Un avis de 5 étoiles rapporte +30 cauris au dev, 4 étoiles +20, 3 étoiles +10.</p>
                                <x-button type="submit" size="lg" class="w-full sm:w-auto">Publier mon avis</x-button>
                            </div>
                        </form>
                    @else
                        <h2 class="text-[28px] sm:text-[32px]">L'avis</h2>
                        <p class="mt-2 text-ink-muted">L'auteur n'a pas encore laissé d'avis.</p>
                    @endif
                </x-card>
            @endif
        </div>

        {{-- Colonne d'action --}}
        <aside id="agir" class="min-w-0 scroll-mt-24 space-y-6" aria-label="Agir sur cette annonce">
            @if ($mission)
                {{-- Cadre repères (un seul par page) : le budget --}}
                <x-frame class="bg-surface p-6">
                    <p class="text-sm text-ink-muted">Budget</p>
                    <x-budget :amount="$listing->budget_fcfa" size="lg" class="mt-1" />
                    <p class="mt-3 text-sm text-ink-muted">Délai souhaité : {{ $listing->delivery_days }} jours</p>
                </x-frame>
            @endif

            @if ($listing->payment)
                @php($payment = $listing->payment)
                <x-card padding="p-6" data-payment>
                    <h2 class="text-2xl">{{ $payment->status->label() }}</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Montant</dt><dd class="font-semibold text-ink">{{ Money::fcfa($payment->amount_fcfa) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Opérateur</dt><dd class="text-ink">{{ $payment->provider->label() }}</dd></div>
                        @if ($isAuthor)
                            <div class="flex justify-between gap-3"><dt class="text-ink-muted">Numéro</dt><dd class="text-ink">{{ $payment->maskedPhone() }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Référence</dt><dd class="font-mono text-ink">{{ $payment->reference }}</dd></div>
                    </dl>
                    <x-simulation-notice class="mt-4" />
                </x-card>
            @endif

            <x-card padding="p-6" data-action-box>
                @if ($isAuthor)
                    @if ($mission && $status === ListingStatus::Open)
                        <h2 class="text-2xl">Ta mission</h2>
                        <p class="mt-2 text-ink-soft">{{ $proposals->count() }} {{ $proposals->count() > 1 ? 'propositions reçues' : 'proposition reçue' }}. Compare-les, puis retiens un dev : tu ne paies rien avant.</p>
                    @elseif ($mission && $status === ListingStatus::InProgress)
                        <h2 class="text-2xl">Mission en cours</h2>
                        <p class="mt-2 text-ink-soft">Quand le travail est livré et vérifié, confirme la fin de la mission.</p>
                        <form method="post" action="{{ route('listings.complete', $listing) }}" class="mt-5">
                            @csrf
                            <x-button type="submit" class="w-full">Confirmer la fin de la mission</x-button>
                        </form>
                        <p class="mt-3 text-center text-sm text-ink-muted">Le dev reçoit +20 cauris et le paiement est libéré.</p>
                    @elseif (! $mission && $status === ListingStatus::Open)
                        <h2 class="text-2xl">Ton équipe</h2>
                        <p class="mt-2 text-ink-soft">{{ $listing->acceptedProposals->count() + 1 }} dans l'équipe. Quand elle est au complet, ferme l'annonce.</p>
                        <form method="post" action="{{ route('listings.close', $listing) }}" class="mt-5">
                            @csrf
                            <x-button type="submit" variant="secondary" class="w-full">Équipe complète</x-button>
                        </form>
                        <p class="mt-3 text-center text-sm text-ink-muted">Les demandes en attente seront refusées.</p>
                    @else
                        <h2 class="text-2xl">{{ $status->label($listing->type) }}</h2>
                        <p class="mt-2 text-ink-soft">Cette annonce n'accepte plus de réponses.</p>
                    @endif

                    @can('cancel', $listing)
                        <form method="post" action="{{ route('listings.cancel', $listing) }}" class="mt-5 border-t border-line pt-4 text-center">
                            @csrf
                            <button type="submit" class="text-sm text-ink-muted underline underline-offset-4 hover:text-ink">Annuler la mission</button>
                            @if ($listing->payment)
                                <p class="mt-1 text-xs text-ink-muted">Les fonds sécurisés te sont remboursés (simulation).</p>
                            @endif
                        </form>
                    @endcan
                @elseif ($myProposal)
                    <h2 class="text-2xl">{{ $mission ? 'Ta proposition' : 'Ta demande' }}</h2>
                    <x-chip :tone="$myProposal->status->tone()" class="mt-3">{{ $myProposal->status->label($listing->type) }}</x-chip>
                    @if ($mission)
                        <p class="mt-3 text-ink-soft">{{ Money::fcfa($myProposal->proposed_fcfa) }} · {{ $myProposal->delivery_days }} jours</p>
                    @endif
                    <p class="mt-3 text-sm text-ink-muted">
                        @switch($myProposal->status)
                            @case(ProposalStatus::Pending) L'auteur compare les réponses : tu verras ici sa décision. @break
                            @case(ProposalStatus::Accepted) {{ $mission ? 'Tu es retenu : les fonds sont sécurisés, tu peux commencer.' : 'Bienvenue dans l\'équipe !' }} @break
                            @default L'auteur a fait un autre choix cette fois. D'autres annonces t'attendent.
                        @endswitch
                    </p>
                @elseif ($canPropose)
                    <h2 class="text-2xl">{{ $mission ? 'Décroche cette mission' : 'Rejoins cette équipe' }}</h2>
                    <form method="post" action="{{ route('proposals.store', $listing) }}" class="mt-4 space-y-4" novalidate>
                        @csrf
                        @if ($mission)
                            <div class="grid grid-cols-2 gap-3">
                                <x-field label="Ton prix (FCFA)" name="proposed_fcfa">
                                    <x-input name="proposed_fcfa" inputmode="numeric" :placeholder="$listing->budget_fcfa" required />
                                </x-field>
                                <x-field label="Délai (jours)" name="delivery_days">
                                    <x-input name="delivery_days" inputmode="numeric" :placeholder="$listing->delivery_days" required />
                                </x-field>
                            </div>
                        @endif
                        <x-field label="Ton message" name="message" :hint="$mission ? 'Ce que tu as déjà fait de proche, comment tu t\'y prendrais.' : 'Ce que tu apportes à l\'équipe, tes disponibilités.'">
                            <x-textarea name="message" rows="5" required maxlength="2000" aria-describedby="message-hint" />
                        </x-field>
                        <x-button type="submit" class="w-full">{{ $mission ? 'Proposer mon aide' : 'Rejoindre l\'équipe' }}</x-button>
                        <p class="text-center text-sm text-ink-muted">{{ $mission ? 'Gratuit · Une seule proposition par mission, soigne-la.' : 'L\'auteur voit ton profil et ton message, puis t\'ajoute à l\'équipe.' }}</p>
                    </form>
                @elseif (! $me && $status === ListingStatus::Open)
                    <h2 class="text-2xl">{{ $mission ? 'Décroche cette mission' : 'Rejoins cette équipe' }}</h2>
                    <p class="mt-2 text-ink-soft">Crée ton profil pour {{ $mission ? 'proposer ton aide' : 'rejoindre l\'équipe' }} : l'auteur voit tes compétences et tes cauris.</p>
                    <x-button :href="route('register')" class="mt-5 w-full">{{ Copy::SIGNUP_CTA }}</x-button>
                    <p class="mt-3 text-center text-sm text-ink-muted">{{ Copy::SIGNUP_REASSURANCE }}</p>
                @else
                    <h2 class="text-2xl">{{ $status->label($listing->type) }}</h2>
                    <p class="mt-2 text-ink-soft">Cette annonce n'accepte plus de réponses. D'autres t'attendent dans les annonces.</p>
                @endif
            </x-card>
        </aside>
    </div>

    {{-- Barre d'action collante (mobile) : l'action principale de l'écran, au-dessus de la barre du bas.
         Masquée quand la zone d'action est visible, pour ne jamais doubler le bouton. --}}
    @if ($primary)
        <div class="fixed inset-x-3 bottom-[5.75rem] z-30 transition-opacity motion-reduce:transition-none lg:hidden" data-action-bar
             x-data="{ hidden: false }"
             x-init="const target = document.querySelector('{{ $primary['href'] === '#agir' ? '[data-action-box]' : $primary['href'] }}'); if (target && 'IntersectionObserver' in window) new IntersectionObserver(([e]) => hidden = e.isIntersecting).observe(target)"
             x-bind:class="hidden && 'pointer-events-none opacity-0'">
            <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface p-2 pl-4 shadow-card-hover">
                @if ($mission)
                    <x-budget :amount="$listing->budget_fcfa" class="min-w-0 [&>span:first-child]:text-xl" />
                @endif
                <x-button :href="$primary['href']" size="sm" class="ml-auto min-w-0 shrink">{{ $primary['label'] }}</x-button>
            </div>
        </div>
    @endif
</x-layouts.app>
