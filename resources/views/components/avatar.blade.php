@props(['user', 'size' => 'md'])

{{-- Avatar rond : photo, ou fond uni primary-50 avec les initiales en Newsreader primary-700. --}}
@php
    $dimensions = match ($size) {
        'stack' => 'size-7 text-[10px] font-sans! font-semibold!',
        'xs' => 'size-8 text-sm',
        'sm' => 'size-10 text-base',
        'lg' => 'size-24 text-3xl sm:size-28',
        default => 'size-14 text-xl',
    };
    $url = $user->relationLoaded('profile') ? $user->profile?->avatarUrl() : null;
@endphp

@if ($url)
    <img src="{{ $url }}" alt="" width="256" height="256" loading="lazy" decoding="async"
         {{ $attributes->merge(['class' => "$dimensions shrink-0 rounded-full object-cover ring-2 ring-surface"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$dimensions inline-flex shrink-0 items-center justify-center rounded-full bg-primary-50 font-serif font-medium text-primary-700 ring-2 ring-surface dark:bg-primary-900 dark:text-primary-200"]) }}
          aria-hidden="true">{{ $user->initials() }}</span>
@endif
