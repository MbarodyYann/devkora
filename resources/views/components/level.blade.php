@props(['user'])

{{-- Niveau du membre (Nouveau, Contributeur, Confirmé, Référent), calculé à partir des cauris. --}}
@php($level = $user->level())
<x-chip :tone="$level === App\Enums\Level::Newcomer ? 'warm' : 'info'" {{ $attributes }}>{{ $level->label() }}</x-chip>
