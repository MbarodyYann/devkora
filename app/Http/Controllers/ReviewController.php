<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Listing;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Listing $listing, ReviewService $reviews): RedirectResponse
    {
        $reviews->create($listing, $request->validated());

        return redirect()
            ->to(route('listings.show', $listing).'#avis')
            ->with('status', 'Avis publié : il apparaît sur le profil du dev et aide les autres à choisir.');
    }
}
