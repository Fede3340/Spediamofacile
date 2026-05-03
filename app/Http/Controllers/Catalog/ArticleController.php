<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleImageUploadRequest;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Services\Security\ImageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    // Lista articoli, filtrabile per tipo (guide o service)
    public function index(Request $request): JsonResponse
    {
        $query = Article::orderBy('sort_order')->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        return response()->json(['data' => $query->get()]);
    }

    // Crea un nuovo articolo
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $article = Article::create($data);

        return response()->json([
            'success' => true,
            'data' => $article,
        ], 201);
    }

    // Mostra un singolo articolo
    public function show(Article $article): JsonResponse
    {
        return response()->json(['data' => $article]);
    }

    // Aggiorna un articolo esistente
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $data = $request->validated();

        $article->update($data);

        return response()->json([
            'success' => true,
            'data' => $article->fresh(),
        ]);
    }

    // Elimina un articolo
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Articolo eliminato con successo.',
        ]);
    }

    // Carica un'immagine per un articolo.
    // Sprint 6.7 security hardening: ArticleImageUploadRequest + ImageSanitizer.
    public function uploadImage(ArticleImageUploadRequest $request, Article $article, ImageSanitizer $sanitizer): JsonResponse
    {
        $path = $sanitizer->sanitizeAndStore(
            $request->file('image'),
            'articles',
            'public'
        );

        $article->update(['featured_image' => Storage::url($path)]);

        return response()->json([
            'success' => true,
            'url' => Storage::url($path),
        ]);
    }
}
