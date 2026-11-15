@php use App\Support\Moderation; @endphp

<x-layouts.app title="Modération" :noindex="true">
    <h1 class="text-[38px] sm:text-5xl">Garde les échanges utiles et sains.</h1>
    <p class="mt-3 max-w-2xl text-ink-soft">Les contenus signalés par la communauté. Masque ce qui doit l'être, classe sans suite le reste : chaque action est journalisée et réversible.</p>

    <section class="mt-10" aria-labelledby="file-titre">
        <h2 id="file-titre" class="text-[28px] sm:text-[32px]">À traiter <span class="text-ink-muted">({{ $pending->count() }})</span></h2>
        @if ($pending->isEmpty())
            <x-empty-state title="Rien à traiter pour l'instant." class="mt-4">La communauté n'a rien signalé de nouveau.</x-empty-state>
        @else
            <ul class="mt-4 space-y-4">
                @foreach ($pending as $key => $item)
                    @php([$type] = explode(':', $key))
                    @php($d = Moderation::describe($item['content']))
                    <li>
                        <x-card padding="p-5 sm:p-6" data-pending>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach ($item['reports']->countBy(fn ($r) => $r->reason->label()) as $label => $count)
                                    <x-chip tone="warm">{{ $label }}{{ $count > 1 ? ' × '.$count : '' }}</x-chip>
                                @endforeach
                            </div>
                            <a href="{{ $d['url'] }}" class="mt-3 block break-words font-serif text-xl text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ Moderation::heading($item['content']) }}</a>
                            <p class="mt-2 line-clamp-3 break-words text-ink-soft">{{ $d['excerpt'] }}</p>
                            <p class="mt-2 text-sm text-ink-muted">Signalé par {{ $item['reports']->pluck('user.name')->join(', ') }}</p>

                            @if ($item['decision']->allowed())
                                <form method="post" class="mt-4 space-y-3 border-t border-line pt-4">
                                    @csrf
                                    <x-field label="Note" name="note" optional>
                                        <x-input name="note" maxlength="500" id="note-{{ $loop->index }}" placeholder="Ex. publicité pour un service sans rapport" />
                                    </x-field>
                                    <div class="flex flex-wrap gap-3">
                                        <x-button type="submit" size="sm" formaction="{{ route('moderation.hide', [$type, $item['content']->id]) }}">Masquer</x-button>
                                        <x-button type="submit" variant="secondary" size="sm" formaction="{{ route('moderation.dismiss', [$type, $item['content']->id]) }}">Classer sans suite</x-button>
                                    </div>
                                </form>
                            @else
                                <p class="mt-4 border-t border-line pt-4 text-sm text-ink-muted">{{ $item['decision']->message() }}</p>
                            @endif
                        </x-card>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($hidden->isNotEmpty())
        <section class="mt-12" aria-labelledby="masques-titre">
            <h2 id="masques-titre" class="text-[28px] sm:text-[32px]">Contenus masqués</h2>
            <x-card padding="p-2 sm:p-3" class="mt-4">
                <ul class="divide-y divide-line">
                    @foreach ($hidden as $content)
                        @php($d = Moderation::describe($content))
                        <li class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <a href="{{ $d['url'] }}" class="block break-words font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ Moderation::heading($content) }}</a>
                                <p class="text-sm text-ink-muted">{{ Moderation::noun($content)['feminine'] ? 'Masquée' : 'Masqué' }} le {{ $content->hidden_at->translatedFormat('j M Y') }}</p>
                            </div>
                            @can('moderate-content', $content)
                                <form method="post" action="{{ route('moderation.restore', [$content->getMorphClass(), $content->id]) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary" size="sm">Rétablir</x-button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </section>
    @endif

    <section class="mt-12" aria-labelledby="journal-titre">
        <h2 id="journal-titre" class="text-[28px] sm:text-[32px]">Journal</h2>
        @if ($journal->isEmpty())
            <p class="mt-3 text-ink-muted">Aucune action pour l'instant.</p>
        @else
            <x-card padding="p-2 sm:p-3" class="mt-4">
                <ul class="divide-y divide-line" data-journal>
                    @foreach ($journal as $action)
                        <li class="p-3 text-sm">
                            <p class="text-ink">
                                <span class="font-semibold">{{ $action->moderator->name }}</span> {{ Moderation::sentence($action->action, $action->target) }}
                            </p>
                            @if ($action->note)
                                <p class="mt-0.5 break-words text-ink-soft">« {{ $action->note }} »</p>
                            @endif
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $action->created_at->translatedFormat('j M Y, G \h i') }}</p>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </section>
</x-layouts.app>
