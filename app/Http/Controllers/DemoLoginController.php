<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Demo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Connexion démo en un clic (APP_DEMO=true seulement, sinon 404).
 */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $persona): RedirectResponse
    {
        abort_unless(Demo::enabled() && isset(Demo::PERSONAS[$persona]), 404);

        $user = User::query()->where('email', Demo::PERSONAS[$persona]['email'])->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        // Pas de message : le bandeau du mode démo indique déjà qui est connecté.
        return redirect()->route('devs.show', $user);
    }
}
