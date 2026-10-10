<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Après l'inscription : direction le profil, avec l'encart de bienvenue (11-redaction.md, « Inscription »).
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->route('devs.show', $request->user())->with('welcome', true);
    }
}
