@props(['title', 'subtitle' => null, 'demo' => false])

{{-- Pages d'authentification : titre centré, panneau de démo éventuel, formulaire dans une carte, lien discret. --}}
<div class="mx-auto w-full max-w-md">
    <div class="mb-8 text-center">
        <h1 class="text-balance text-[38px] sm:text-5xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-4 text-ink-soft">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($demo)
        <x-demo-panel class="mb-6" />
    @endif
    <x-card padding="p-5 sm:p-8">
        {{ $slot }}
    </x-card>
    @isset($footer)
        <p class="mt-6 text-center">{{ $footer }}</p>
    @endisset
</div>
