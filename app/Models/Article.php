<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'title',            // Titolo dell'articolo
        'slug',             // URL-friendly (es. "come-spedire-un-pacco")
        'type',             // Tipo: "guide" (guida) o "service" (servizio)
        'meta_description', // Descrizione per i motori di ricerca (SEO)
        'intro',            // Testo introduttivo
        'sections',         // Array JSON di sezioni [{heading, text}, ...]
        'faqs',             // Array JSON di FAQ [{title, text}, ...]
        'featured_image',   // URL dell'immagine di copertina
        'icon',             // Icona SVG o nome icona
        'is_published',     // Se l'articolo e' visibile al pubblico
        'sort_order',       // Ordine di visualizzazione (piu' basso = prima)
    ];

    /**
     * Conversioni automatiche dei tipi.
     * sections e faqs vengono convertiti da JSON stringa ad array PHP e viceversa.
     */
    protected $casts = [
        'sections' => 'array',
        'faqs' => 'array',
        'is_published' => 'boolean',
    ];

    // Scope: filtra solo le guide (type = "guide")
    public function scopeGuides($query) { return $query->where('type', 'guide'); }

    // Scope: filtra solo i servizi (type = "service")
    public function scopeServices($query) { return $query->where('type', 'service'); }

    // Scope: filtra solo gli articoli pubblicati
    public function scopePublished($query) { return $query->where('is_published', true); }
}
