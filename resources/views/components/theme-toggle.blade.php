{{-- Bascule clair / sombre en contrôle segmenté. La classe .dark est posée avant le rendu par le script
     anti-flash du <head> ; le choix est gardé dans localStorage (préférence système par défaut). --}}
<div role="radiogroup" aria-label="Thème"
     x-data="{ dark: document.documentElement.classList.contains('dark'), set(value) { this.dark = value; document.documentElement.classList.toggle('dark', value); try { localStorage.setItem('theme', value ? 'dark' : 'light') } catch (e) {} } }"
     {{ $attributes->merge(['class' => 'inline-flex shrink-0 rounded-[10px] bg-bg-alt p-1 ring-1 ring-line']) }}>
    <button type="button" role="radio" x-bind:aria-checked="(! dark).toString()" aria-checked="true" x-on:click="set(false)"
            class="inline-flex size-8 items-center justify-center rounded-[7px] text-ink-muted transition hover:text-ink aria-checked:bg-surface aria-checked:text-ink aria-checked:shadow-soft">
        <x-icon name="sun" class="size-4" /><span class="sr-only">Clair</span>
    </button>
    <button type="button" role="radio" x-bind:aria-checked="dark.toString()" aria-checked="false" x-on:click="set(true)"
            class="inline-flex size-8 items-center justify-center rounded-[7px] text-ink-muted transition hover:text-ink aria-checked:bg-surface aria-checked:text-ink aria-checked:shadow-soft">
        <x-icon name="moon" class="size-4" /><span class="sr-only">Sombre</span>
    </button>
</div>
