<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Support\French;
use App\Support\Text;

class EventService
{
    public function __construct(private readonly MarkdownRenderer $markdown) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function publish(User $author, array $data): Event
    {
        $event = new Event(['title' => French::typography($data['title'])] + $data);
        $event->forceFill([
            'user_id' => $author->id,
            'description_html' => $this->markdown->toHtml($data['description']),
            'excerpt' => Text::excerpt($data['description']),
        ])->save();

        return $event;
    }

    /** « J'y participe » / « Je n'y vais plus » : renvoie la participation après l'opération. */
    public function toggle(User $user, Event $event): bool
    {
        $result = $event->participants()->toggle($user->id);

        return $result['attached'] !== [];
    }
}
