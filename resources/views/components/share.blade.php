@props(['title', 'url'])

{{-- Partage (02, P2) : WhatsApp (lien wa.me avec titre et adresse) et « Copier le lien ». --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }} data-share>
    <a href="https://wa.me/?text={{ rawurlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer"
       class="btn-outline inline-flex min-h-10 items-center gap-2 rounded-btn px-3.5 text-sm font-semibold">
        <x-icon name="share" class="size-4" /> Partager sur WhatsApp
    </a>
    <button type="button" x-data="{ copied: false }"
            x-on:click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            class="btn-outline inline-flex min-h-10 items-center gap-2 rounded-btn px-3.5 text-sm font-semibold">
        <x-icon name="link" class="size-4" /> <span x-text="copied ? 'Lien copié' : 'Copier le lien'">Copier le lien</span>
    </button>
</div>
