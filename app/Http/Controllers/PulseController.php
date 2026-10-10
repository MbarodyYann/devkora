<?php

namespace App\Http\Controllers;

use App\Services\PulseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PulseController extends Controller
{
    public function __invoke(Request $request, PulseService $pulse): View
    {
        $countries = $pulse->demandCountries();
        $country = array_key_exists((string) $request->query('pays'), $countries) ? $request->query('pays') : null;

        return view('pulse', [
            'rates' => $pulse->rates(),
            'demanded' => $pulse->demandedSkills($country),
            'countries' => $countries,
            'country' => $country,
            'devsByCountry' => $pulse->devsByCountry(),
            'activity' => $pulse->activity(),
        ]);
    }
}
