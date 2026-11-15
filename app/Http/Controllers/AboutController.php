<?php

namespace App\Http\Controllers;

use App\Services\CommunityStats;
use App\Support\Credits;
use Illuminate\View\View;

/**
 * À propos : la présentation de DevKora, ses chiffres réels, l'équipe, l'usage de l'IA et les licences.
 */
class AboutController extends Controller
{
    public function __invoke(CommunityStats $stats): View
    {
        $credits = collect(Credits::direct());

        return view('about', [
            'stats' => $stats->get(),
            'appCredits' => $credits->where('dev', false),
            'toolCredits' => $credits->where('dev', true),
            'creditsSummary' => Credits::summary(),
        ]);
    }
}
