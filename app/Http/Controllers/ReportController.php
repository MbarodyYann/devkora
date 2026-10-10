<?php

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Models\Report;
use App\Support\Moderation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Signalement (02, P1) : un par membre et par contenu, jamais sur son propre contenu.
 */
class ReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Moderation::TYPES))],
            'id' => ['required', 'integer'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
        ], [
            'reason.required' => 'Choisis la raison du signalement.',
        ]);

        $content = (Moderation::TYPES[$data['type']])::query()->whereNull('hidden_at')->findOrFail($data['id']);
        $user = $request->user();

        if ($content->user_id === $user->id) {
            return back()->with('status', 'C\'est ton propre contenu : tu peux le modifier, pas le signaler.');
        }

        $already = Report::query()
            ->where('user_id', $user->id)
            ->where('reportable_type', $content->getMorphClass())
            ->where('reportable_id', $content->getKey())
            ->exists();

        if ($already) {
            return back()->with('status', 'Tu as déjà signalé ce contenu : un modérateur va le regarder.');
        }

        (new Report)->forceFill([
            'user_id' => $user->id,
            'reportable_type' => $content->getMorphClass(),
            'reportable_id' => $content->getKey(),
            'reason' => ReportReason::from($data['reason']),
        ])->save();

        return back()->with('status', 'Merci : un modérateur de la communauté va regarder ce contenu.');
    }
}
