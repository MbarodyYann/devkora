@props(['notification'])

{{-- Une notification : pastille ambre si non lue (et « non lue » pour les lecteurs d'écran), message, date relative. --}}
@php($unread = $notification->read_at === null)
<a href="{{ route('notifications.open', $notification->id) }}"
   {{ $attributes->merge(['class' => 'flex min-w-0 items-start gap-3 rounded-xl px-3 py-2.5 transition hover:bg-bg-alt']) }}>
    <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-secondary-400' => $unread, 'bg-transparent' => ! $unread]) aria-hidden="true"></span>
    <span class="min-w-0 flex-1">
        <span @class(['block break-words text-sm', 'font-semibold text-ink' => $unread, 'text-ink-soft' => ! $unread])>
            @if ($unread)<span class="sr-only">Non lue : </span>@endif{{ $notification->data['message'] ?? '' }}
        </span>
        <span class="mt-0.5 block text-xs text-ink-muted">{{ $notification->created_at->diffForHumans() }}</span>
    </span>
</a>
