@props(['name', 'type' => 'text', 'value' => null])

{{-- Champ : 44 px, coins de 10 px ; focus : bordure primary-600 et anneau primary-100 de 3 px. --}}
@php($describedBy = trim(($errors->has($name) ? $name.'-error ' : '').($attributes->get('aria-describedby') ?? '')))
<input type="{{ $type }}" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
       @if (! in_array($type, ['password', 'file'], true)) value="{{ old($name, $value) }}" @endif
       @error($name) aria-invalid="true" @enderror
       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
       {{ $attributes->except(['id', 'aria-describedby'])->class([
           'block w-full min-w-0 min-h-11 rounded-field border bg-surface px-3.5 text-base text-ink placeholder:text-ink-muted',
           'transition focus:border-primary-600 focus:outline-none focus:ring-3 focus:ring-primary-100 dark:focus:border-primary-300 dark:focus:ring-primary-800',
           'border-secondary-500' => $errors->has($name),
           'border-line' => ! $errors->has($name),
       ]) }}>
