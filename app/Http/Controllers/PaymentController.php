<?php

namespace App\Http\Controllers;

use App\Enums\PaymentProvider;
use App\Enums\ProposalStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Proposal;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Retenir un dev = sécuriser les fonds, en simulation (02, P1), en deux étapes :
 * choix de l'opérateur et du numéro, puis code de confirmation simulé.
 */
class PaymentController extends Controller
{
    public function create(Listing $listing, Proposal $proposal): View
    {
        $this->authorizeHire($listing, $proposal);

        return view('payments.create', [
            'listing' => $listing,
            'proposal' => $proposal->load('user.profile'),
            'providers' => PaymentProvider::options(),
        ]);
    }

    public function store(StorePaymentRequest $request, Listing $listing, Proposal $proposal, PaymentService $payments): RedirectResponse
    {
        $this->authorizeHire($listing, $proposal);

        // Rien n'est débité : on garde le choix en session et on affiche un code simulé à saisir.
        $request->session()->put("payment.$listing->id", [
            'proposal_id' => $proposal->id,
            'provider' => $request->validated('provider'),
            'phone' => $request->phone(),
            'code' => $payments->confirmationCode(),
        ]);

        return redirect()->route('payments.confirm', $listing);
    }

    public function confirm(Request $request, Listing $listing): View|RedirectResponse
    {
        Gate::authorize('hire', $listing);
        $pending = $request->session()->get("payment.$listing->id");
        if (! $pending) {
            return redirect()->route('listings.show', $listing);
        }

        return view('payments.confirm', [
            'listing' => $listing,
            'proposal' => Proposal::with('user')->findOrFail($pending['proposal_id']),
            'pending' => $pending,
            'provider' => PaymentProvider::from($pending['provider']),
            'maskedPhone' => Payment::mask($pending['phone']),
        ]);
    }

    public function verify(Request $request, Listing $listing, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('hire', $listing);
        $pending = $request->session()->get("payment.$listing->id");
        abort_unless($pending, 404);

        $request->validate(['code' => ['required', 'string']], ['code.required' => 'Saisis le code de confirmation.']);
        if (! hash_equals($pending['code'], preg_replace('/\D/', '', (string) $request->input('code')))) {
            throw ValidationException::withMessages(['code' => 'Ce code ne correspond pas. Vérifie les 6 chiffres affichés.']);
        }

        $proposal = Proposal::with('user')->findOrFail($pending['proposal_id']);
        $this->authorizeHire($listing, $proposal);
        $payment = $payments->secure($listing, $proposal, PaymentProvider::from($pending['provider']), $pending['phone']);
        $request->session()->forget("payment.$listing->id");

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Fonds sécurisés (référence '.$payment->reference.'). '.$proposal->user->firstName().' peut commencer : le paiement sera libéré quand tu confirmeras la fin de la mission.');
    }

    private function authorizeHire(Listing $listing, Proposal $proposal): void
    {
        Gate::authorize('hire', $listing);
        abort_unless($proposal->listing_id === $listing->id && $proposal->status === ProposalStatus::Pending, 404);
    }
}
