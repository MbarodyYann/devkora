{{-- Logo provisoire (08, « Logo ») : mot-symbole « DevKora » en Newsreader 600, « Dev » en ink,
     « Kora » en primary-600 (primary-300 en sombre). Source : App\Support\Brand. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-baseline font-serif text-2xl font-semibold leading-none tracking-[-0.01em]']) }}>
    <span class="text-ink">{{ App\Support\Brand::WORD_START }}</span><span class="text-primary-600 dark:text-primary-300">{{ App\Support\Brand::WORD_END }}</span>
</span>
