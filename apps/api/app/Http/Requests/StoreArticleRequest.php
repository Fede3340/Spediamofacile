<?php

namespace App\Http\Requests;

use App\Http\Controllers\Catalog\ArticleController;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/admin/articles (creazione articolo CMS).
 * type ammesso: vedi ArticleController::ALLOWED_TYPES (faq, guide, ecc.).
 */
class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:articles,slug'],
            'type' => ['required', 'in:' . ArticleController::ALLOWED_TYPES],
            'meta_description' => ['nullable', 'string'],
            'intro' => ['nullable', 'string'],
            'sections' => ['nullable', 'array'],
            'sections.*.heading' => ['required_with:sections', 'string'],
            'sections.*.text' => ['required_with:sections', 'string'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.title' => ['required_with:faqs', 'string'],
            'faqs.*.text' => ['required_with:faqs', 'string'],
            'featured_image' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer'],
        ];
    }
}
