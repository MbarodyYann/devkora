@props(['options', 'model', 'label'])

{{-- Contrôle segmenté lié à une propriété Livewire : conteneur bg-alt, élément actif blanc avec ombre douce.
     Défile dans son conteneur si la place manque (jamais la page). --}}
<div role="radiogroup" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'inline-flex max-w-full overflow-x-auto rounded-[10px] bg-bg-alt p-1 ring-1 ring-line [scrollbar-width:none] [&::-webkit-scrollbar]:hidden']) }}>
    @foreach ($options as $value => $optionLabel)
        <label class="relative shrink-0 cursor-pointer">
            <input type="radio" name="{{ $model }}" value="{{ $value }}" wire:model.live="{{ $model }}" class="peer sr-only">
            <span class="flex min-h-9 items-center whitespace-nowrap rounded-[7px] px-3 text-sm font-medium text-ink-muted transition hover:text-ink peer-checked:bg-surface peer-checked:text-ink peer-checked:shadow-soft peer-focus-visible:outline-2 peer-focus-visible:outline-primary-500">{{ $optionLabel }}</span>
        </label>
    @endforeach
</div>
