<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\ListingType;
use App\Models\Event;
use App\Policies\ModerationPolicy;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function show(Event $event): View
    {
        $me = auth()->user();
        abort_unless($event->hidden_at === null || $me?->id === $event->user_id || ModerationPolicy::canModerate($me), 404);

        $event->load(['user.profile'])->loadCount('participants');

        return view('events.show', [
            'event' => $event,
            'participants' => $event->participants()->with('profile')->latest('event_user.created_at')->limit(12)->get(),
            'participating' => $me !== null && $event->participants()->whereKey($me->id)->exists(),
            // Hackathon : les équipes qui se forment pour l'événement.
            'teams' => $event->type === EventType::Hackathon
                ? $event->listings()->visible()->where('type', ListingType::Team)->with(['user.profile', 'acceptedProposals.user.profile'])->withCount('proposals')->latest()->get()
                : collect(),
        ]);
    }

    /** « J'y participe » / « Je n'y vais plus ». */
    public function participate(Request $request, Event $event, EventService $events): RedirectResponse
    {
        abort_if($event->hidden_at !== null || $event->isPast(), 403, 'Cet événement est terminé.');

        $going = $events->toggle($request->user(), $event);

        return back()->with('status', $going ? 'C\'est noté : tu participes. À bientôt !' : 'C\'est noté : tu n\'y vas plus.');
    }
}
