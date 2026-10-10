<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notification dans l'application (cloche) : un type, un message déjà rédigé et un lien.
 * Les messages sont écrits par App\Services\Notifier, qui choisit aussi les destinataires.
 */
class Activity extends Notification
{
    /**
     * @param  array<string, mixed>  $extra  données de regroupement (ex. nombre de votes « Utile »)
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $message,
        public readonly string $url,
        public readonly array $extra = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'message' => $this->message, 'url' => $this->url] + $this->extra;
    }
}
