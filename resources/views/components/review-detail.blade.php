@props(['review', 'mission' => null])

{{-- Un avis : note, critères, commentaire, auteur, et la mission concernée si demandé. --}}
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <x-stars :value="$review->rating" />
        <span class="text-sm text-ink-muted">par {{ $review->reviewer->name }}, le {{ $review->created_at->translatedFormat('j M Y') }}</span>
    </div>
    @if ($mission)
        <a href="{{ route('listings.show', $mission) }}" class="mt-2 block font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $mission->title }}</a>
    @endif
    @if ($review->comment)
        <p class="mt-2 whitespace-pre-line break-words text-ink-soft">{{ $review->comment }}</p>
    @endif
    <dl class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm">
        @foreach (App\Models\Review::CRITERIA as $field => $label)
            <div class="flex items-center gap-1.5"><dt class="text-ink-muted">{{ $label }}</dt><dd class="font-semibold text-ink">{{ $review->$field }}/5</dd></div>
        @endforeach
    </dl>
</div>
