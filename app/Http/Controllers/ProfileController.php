<?php

namespace App\Http\Controllers;

use App\Enums\Availability;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Skill;
use App\Models\User;
use App\Services\AvatarService;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user): View
    {
        abort_unless($user->profile()->exists(), 404);

        // « Ce qu'il partage » (02) : ses réponses acceptées, puis ses questions.
        $acceptedAnswers = $user->answers()->visible()
            ->whereHas('question', fn ($q) => $q->visible()->whereColumn('questions.accepted_answer_id', 'answers.id'))
            ->with('question:id,title,answers_count')
            ->latest()->limit(5)->get();

        return view('devs.show', [
            'dev' => $user->load(['profile', 'skills']),
            'acceptedAnswers' => $acceptedAnswers,
            'acceptedCount' => $user->answers()->visible()->whereHas('question', fn ($q) => $q->visible()->whereColumn('questions.accepted_answer_id', 'answers.id'))->count(),
            'questions' => $user->questions()->visible()->latest()->limit(5)->get(['id', 'title', 'answers_count', 'accepted_answer_id', 'created_at']),
            'questionsCount' => $user->questions()->visible()->count(),
        ]);
    }

    public function edit(User $user): View
    {
        $user->load(['profile', 'skills']);
        Gate::authorize('update', $user->profile);

        return view('profile.edit', [
            'dev' => $user,
            'countries' => Countries::grouped(),
            'availabilities' => Availability::options(),
            'languageOptions' => config('languages'),
            'suggestedSkills' => Skill::query()->orderByDesc('is_local')->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateProfileRequest $request, User $user, AvatarService $avatars): RedirectResponse
    {
        $profile = $user->profile;
        $data = $request->safe()->except(['name', 'avatar', 'skills', 'languages']);
        // Langues dans l'ordre de config/languages.php.
        $data['languages'] = array_values(array_intersect(array_keys(config('languages')), $request->validated('languages')));

        if ($request->hasFile('avatar')) {
            $oldAvatar = $profile->avatar_path;
            $data['avatar_path'] = $avatars->store($request->file('avatar'));
        }

        DB::transaction(function () use ($request, $user, $profile, $data) {
            $user->update(['name' => Str::squish($request->validated('name'))]);
            $profile->update($data);
            $user->skills()->sync(Skill::idsFor($request->skillNames()));
        });

        if (isset($oldAvatar)) {
            $avatars->delete($oldAvatar);
        }

        return redirect()
            ->route('devs.show', $user)
            ->with('status', 'Profil mis à jour. Les autres devs te trouvent maintenant avec ces informations.');
    }
}
