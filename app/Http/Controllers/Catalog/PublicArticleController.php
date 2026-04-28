<?php
namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;

use App\Models\Article;
use Illuminate\Http\JsonResponse;

class PublicArticleController extends Controller
{
    private const LIST_COLUMNS = ['id', 'title', 'slug', 'meta_description', 'intro', 'icon', 'featured_image', 'sort_order', 'created_at', 'updated_at'];

    private function publishedList(string $type): JsonResponse
    {
        $articles = Article::query()
            ->where('type', $type)
            ->published()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get(self::LIST_COLUMNS);

        return response()->json(['data' => $articles]);
    }

    private function publishedDetail(string $type, string $slug, string $notFoundMessage): JsonResponse
    {
        $article = Article::query()
            ->where('type', $type)
            ->published()
            ->where('slug', $slug)
            ->first();

        if (!$article) {
            return response()->json(['message' => $notFoundMessage], 404);
        }

        return response()->json(['data' => $article]);
    }

    // Lista guide pubblicate
    public function guides(): JsonResponse
    {
        return $this->publishedList('guide');
    }

    // Singola guida per slug
    public function guide(string $slug): JsonResponse
    {
        return $this->publishedDetail('guide', $slug, 'Guida non trovata.');
    }

    // Lista servizi pubblicati
    public function services(): JsonResponse
    {
        return $this->publishedList('service');
    }

    // Singolo servizio per slug
    public function service(string $slug): JsonResponse
    {
        return $this->publishedDetail('service', $slug, 'Servizio non trovato.');
    }
}
