<?php

namespace Database\Seeders;

use App\Enums\ReportReason;
use App\Models\Listing;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\AnswerService;
use App\Services\ModerationService;
use App\Services\QuestionService;
use App\Support\Demo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Modération de démonstration : de quoi montrer la file à Koffi (modérateur communautaire).
 * Trois contenus signalés en attente (aucun de Koffi, aucun signalé par lui), et une action déjà passée
 * au journal par un autre modérateur.
 */
class ModerationSeeder extends Seeder
{
    public function __construct(
        private readonly QuestionService $questions,
        private readonly AnswerService $answers,
        private readonly ModerationService $moderation,
    ) {}

    public function run(): void
    {
        $clock = DemoClock::make();
        $previousNow = Carbon::getTestNow();
        $member = fn (string $name) => User::where('name', $name)->sole();
        $ange = $member('Ange Yapo');
        $koffi = User::where('email', Demo::KOFFI_EMAIL)->sole();
        // Toujours après l'inscription d'Ange, dans la fenêtre.
        $at = fn (Carbon $moment) => $clock->clamp($moment->max($ange->created_at->copy()->addHour()));

        try {
            // 1. Une question hors sujet (petite annonce).
            Carbon::setTestNow($at($clock->now->copy()->subHours(30)));
            $offTopic = $this->questions->publish($ange, 'Qui vend un téléphone d\'occasion pas cher à Yamoussoukro ?',
                "Je cherche un téléphone d'occasion en bon état, budget 60 000 FCFA. Écrivez-moi si vous en avez un à vendre.", ['Android']);
            $this->report($offTopic, ['Yao Kouassi' => ReportReason::OffTopic, 'Kadiatou Diallo' => ReportReason::OffTopic], $at($clock->now->copy()->subHours(20)));

            // 2. Une réponse publicitaire.
            $wave = Question::where('title', 'like', 'Webhook Wave%')->sole();
            Carbon::setTestNow($at($clock->now->copy()->subHours(14)));
            $promo = $this->answers->publish($ange, $wave, 'Je fais toutes les intégrations de paiement à prix cassé, 5 000 FCFA seulement. Écrivez-moi sur WhatsApp au +225 07 00 00 00 00, réponse garantie en 5 minutes !');
            $this->report($promo, ['Rodrigue Hounkpatin' => ReportReason::Spam], $at($clock->now->copy()->subHours(10)));

            // 3. Une annonce signalée à tort (« Autre ») : à classer sans suite.
            $startup = Listing::where('title', 'like', 'Startup edtech%')->sole();
            $this->report($startup, ['Otieno Odhiambo' => ReportReason::Other], $clock->now->copy()->subHours(6));

            // Une action passée, par un autre modérateur : une réponse publicitaire déjà masquée.
            $orange = Question::where('title', 'like', 'Orange Money Web Payment%')->sole();
            Carbon::setTestNow($at($clock->now->copy()->subDays(2)));
            $old = $this->answers->publish($ange, $orange, 'Formation complète pour devenir développeur en 7 jours garantie, 10 000 FCFA, places limitées, contactez-moi vite sur WhatsApp !');
            $this->report($old, ['Mamadou Bah' => ReportReason::Spam], $at($clock->now->copy()->subDays(2))->addHours(2));
            Carbon::setTestNow($at($clock->now->copy()->subDays(2))->addHours(5)->min($clock->now));
            $this->moderation->hide($member('Christian Mbarga'), $old, 'Publicité sans rapport avec la question.');
        } finally {
            Carbon::setTestNow($previousNow);
        }

        abort_if(Report::whereNull('resolved_at')->where('user_id', $koffi->id)->exists(), 500, 'Koffi ne doit pas avoir signalé les contenus de sa démo.');
    }

    /**
     * @param  array<string, ReportReason>  $reporters
     */
    private function report(object $content, array $reporters, Carbon $at): void
    {
        foreach (array_keys($reporters) as $i => $name) {
            Carbon::setTestNow($at->copy()->addMinutes(37 * $i));
            (new Report)->forceFill([
                'user_id' => User::where('name', $name)->value('id'),
                'reportable_type' => $content->getMorphClass(),
                'reportable_id' => $content->getKey(),
                'reason' => $reporters[$name],
            ])->save();
        }
    }
}
