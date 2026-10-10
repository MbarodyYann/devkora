<div>
    <x-input name="title" wire:model.live.debounce.400ms="title" required minlength="15" maxlength="150" autofocus autocomplete="off"
             aria-describedby="title-hint" placeholder="Ex. Le webhook Wave renvoie 401 en production" />
    @if ($this->similar->isNotEmpty())
        <div class="mt-3 rounded-field border border-line bg-bg-alt p-3" data-similar aria-live="polite">
            <p class="text-sm font-semibold text-ink">Ta question a peut-être déjà une réponse</p>
            <ul class="mt-2 space-y-1.5">
                @foreach ($this->similar as $question)
                    <li class="flex items-start gap-2 text-sm">
                        @if ($question->accepted_answer_id)
                            <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-primary-600 dark:text-secondary-400" />
                        @else
                            <x-icon name="message" class="mt-0.5 size-4 shrink-0 text-ink-muted" />
                        @endif
                        <a href="{{ route('questions.show', $question) }}" target="_blank" class="min-w-0 break-words text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">{{ $question->title }}</a>
                        <span class="shrink-0 text-ink-muted">{{ $question->answers_count }} rép.</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-ink-muted">Rien ne correspond ? Continue : ta question sera publiée.</p>
        </div>
    @endif
</div>
