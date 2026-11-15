<?php

namespace App\Http\Controllers;

use App\Services\CommunityStats;
use App\Services\Contributors;
use App\Services\PulseService;
use App\Support\Credits;
use Illuminate\View\View;

/**
 * À propos : la présentation de DevKora (promesse, chiffres réels, piliers, aperçu du Pouls),
 * puis l'identité, l'équipe, les licences et l'usage de l'IA.
 */
class AboutController extends Controller
{
    /** Nombre de compétences de l'aperçu « Combien facturer ? ». */
    public const RATES_PREVIEW = 4;

    public function __invoke(CommunityStats $stats, PulseService $pulse, Contributors $contributors): View
    {
        $credits = collect(Credits::direct());

        return view('about', [
            'stats' => $stats->get(),
            // Les compétences les mieux documentées du Pouls (3 montants au moins chacune).
            'rates' => array_slice($pulse->rates(), 0, self::RATES_PREVIEW),
            'contributors' => $contributors->ofMonth(5),
            'appCredits' => $credits->where('dev', false),
            'toolCredits' => $credits->where('dev', true),
            'creditsSummary' => Credits::summary(),
        ]);
    }
}
