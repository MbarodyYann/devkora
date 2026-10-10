<?php

namespace App\Services;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\Question;
use App\Models\Report;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Modération communautaire (02, « Privilèges ») : masquer, rétablir, classer sans suite, modifier les tags.
 * Chaque action est journalisée dans moderation_actions et réversible.
 */
class ModerationService
{
    public function hide(User $moderator, Model $content, ?string $note = null): void
    {
        DB::transaction(function () use ($moderator, $content, $note) {
            $content->forceFill(['hidden_at' => now()])->save();
            $report = $this->resolveReports($content);
            $this->log($moderator, $content, ModerationActionType::Hide, $note, $report);
        });
    }

    public function restore(User $moderator, Model $content, ?string $note = null): void
    {
        DB::transaction(function () use ($moderator, $content, $note) {
            $content->forceFill(['hidden_at' => null])->save();
            $this->log($moderator, $content, ModerationActionType::Restore, $note);
        });
    }

    public function dismiss(User $moderator, Model $content, ?string $note = null): void
    {
        DB::transaction(function () use ($moderator, $content, $note) {
            $report = $this->resolveReports($content);
            $this->log($moderator, $content, ModerationActionType::Dismiss, $note, $report);
        });
    }

    /**
     * @param  list<string>  $tags
     */
    public function retag(User $moderator, Question $question, array $tags): void
    {
        DB::transaction(function () use ($moderator, $question, $tags) {
            $before = $question->skills()->pluck('name')->join(', ');
            $question->skills()->sync(Skill::idsFor($tags));
            $this->log($moderator, $question, ModerationActionType::Retag, mb_substr("$before → ".implode(', ', $tags), 0, 500));
        });
    }

    /** Classe les signalements en attente du contenu ; renvoie le premier, pour le journal. */
    private function resolveReports(Model $content): ?Report
    {
        $reports = Report::query()
            ->where('reportable_type', $content->getMorphClass())
            ->where('reportable_id', $content->getKey())
            ->whereNull('resolved_at');

        $first = (clone $reports)->oldest('id')->first();
        $reports->update(['resolved_at' => now()]);

        return $first;
    }

    private function log(User $moderator, Model $content, ModerationActionType $action, ?string $note, ?Report $report = null): void
    {
        (new ModerationAction)->forceFill([
            'moderator_id' => $moderator->id,
            'report_id' => $report?->id,
            'target_type' => $content->getMorphClass(),
            'target_id' => $content->getKey(),
            'action' => $action,
            'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ])->save();
    }
}
