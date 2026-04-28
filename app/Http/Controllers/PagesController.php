<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller pagine pubbliche statiche/semi-statiche servite via Inertia.
 * Sostituisce 7+ Nuxt pages con render Vue lato client + dati shared dal server.
 */
class PagesController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Home', [
            'startingPrice' => '8,90',
        ]);
    }

    public function chiSiamo(): Response
    {
        return Inertia::render('Static/ChiSiamo');
    }

    public function contatti(): Response
    {
        return Inertia::render('Static/Contatti');
    }

    public function faq(): Response
    {
        return Inertia::render('Static/Faq');
    }

    public function privacy(): Response
    {
        return Inertia::render('Static/PrivacyPolicy');
    }

    public function cookie(): Response
    {
        return Inertia::render('Static/CookiePolicy');
    }

    public function termini(): Response
    {
        return Inertia::render('Static/TerminiECondizioni');
    }

    public function tracciaForm(): Response
    {
        return Inertia::render('Static/Traccia');
    }

    public function guide(): Response
    {
        return Inertia::render('Static/Guide', [
            'guides' => [],
        ]);
    }
}
