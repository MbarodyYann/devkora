<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Services\ModerationService;
use App\Support\Moderation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * File de modération communautaire (/moderation), à partir de 250 cauris.
 */
class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('moderate');

        // Signalements en attente, regroupés par contenu.
        $withQuestion = fn (MorphTo $morph) => $morph->morphWith([Answer::class => ['question']]);
        $pending = Report::query()->whereNull('resolved_at')->with(['reportable' => $withQuestion, 'user'])->oldest()->get()
            ->filter(fn (Report $r) => $r->reportable !== null)
            ->groupBy(fn (Report $r) => $r->reportable_type.':'.$r->reportable_id)
            ->map(fn ($reports) => [
                'content' => $reports->first()->reportable,
                'reports' => $reports,
                'decision' => Gate::inspect('moderate-content', $reports->first()->reportable),
            ]);

        // Contenus masqués, à rétablir si besoin.
        $hidden = collect(Moderation::TYPES)
            ->flatMap(fn (string $class) => $class::query()->whereNotNull('hidden_at')->when($class === Answer::class, fn ($q) => $q->with('question'))->latest('hidden_at')->limit(10)->get())
            ->sortByDesc('hidden_at')->take(10)->values();

        return view('moderation.index', [
            'pending' => $pending,
            'hidden' => $hidden,
            'journal' => ModerationAction::query()->with(['moderator', 'target' => $withQuestion])->latest()->limit(30)->get(),
        ]);
    }

    public function hide(Request $request, string $type, int $id, ModerationService $moderation): RedirectResponse
    {
        $content = $this->authorizeContent($type, $id);
        $moderation->hide($request->user(), $content, $this->note($request));

        return back()->with('status', 'Contenu masqué. L\'action est dans le journal, et réversible.');
    }

    public function restore(Request $request, string $type, int $id, ModerationService $moderation): RedirectResponse
    {
        $content = $this->authorizeContent($type, $id);
        $moderation->restore($request->user(), $content, $this->note($request));

        return back()->with('status', 'Contenu rétabli.');
    }

    public function dismiss(Request $request, string $type, int $id, ModerationService $moderation): RedirectResponse
    {
        $content = $this->authorizeContent($type, $id);
        $moderation->dismiss($request->user(), $content, $this->note($request));

        return back()->with('status', 'Signalements classés sans suite.');
    }

    private function authorizeContent(string $type, int $id): Model
    {
        abort_unless(isset(Moderation::TYPES[$type]), 404);
        $content = (Moderation::TYPES[$type])::query()->findOrFail($id);
        Gate::authorize('moderate-content', $content);

        return $content;
    }

    private function note(Request $request): ?string
    {
        return $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;
    }
}
