<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ServiziController extends Controller
{
    public function index(): Response
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['slug', 'title', 'description', 'meta_description'])
            ->map(fn ($s) => [
                'slug' => $s->slug,
                'title' => $s->title,
                'description' => $s->description ?: $s->meta_description,
                'badge' => 'Servizio',
            ])
            ->values();

        return Inertia::render('Static/Servizi', [
            'services' => $services,
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        $service = Service::where('slug', $slug)->where('is_active', true)->first();
        if (! $service) {
            return redirect('/servizi');
        }

        return Inertia::render('Static/ServizioDetail', [
            'service' => [
                'slug' => $service->slug,
                'title' => $service->title,
                'description' => $service->description ?: $service->meta_description,
                'body' => $service->body ?? '',
            ],
        ]);
    }
}
