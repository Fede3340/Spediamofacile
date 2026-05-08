<?php

namespace App\Http\Requests;

use App\Http\Controllers\Catalog\ArticleController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validazione per PUT/PATCH /api/admin/articles/{article}.
 * Tutti i campi opzionali (sometimes); slug unique escludendo l'id corrente.
 */
class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $articleId = $this->route('article')->id ?? 'NULL';

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'unique:articles,slug,'.$articleId],
            'type' => ['sometimes', 'required', Rule::in(ArticleController::ALLOWED_TYPES)],
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
