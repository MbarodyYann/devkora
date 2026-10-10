{{-- Logo provisoire (08, « Logo ») : mot-symbole « DevKora » en Newsreader 600, « Dev » en ink,
     « Kora » en primary-600 (secondary-400 en sombre et sur les bandeaux indigo). Source : App\Support\Brand. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-baseline font-serif text-[26px] font-semibold leading-none tracking-[-0.02em]']) }}>
    <span class="text-ink">{{ App\Support\Brand::WORD_START }}</span><span class="logo-end text-primary-600 dark:text-secondary-400">{{ App\Support\Brand::WORD_END }}</span>
</span>
