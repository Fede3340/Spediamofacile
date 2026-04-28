<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceBand extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'type',            // Tipo di fascia: "weight" (peso) o "volume" (volume)
        'min_value',       // Valore minimo del range (kg o m3)
        'max_value',       // Valore massimo del range (kg o m3)
        'base_price',      // Prezzo pieno in centesimi (es. 890 = 8,90 euro)
        'discount_price',  // Prezzo scontato in centesimi (null = nessuno sconto)
        'show_discount',   // Se mostrare il badge "sconto" nel frontend
        'sort_order',      // Ordine di visualizzazione
    ];

    /**
     * Conversioni automatiche dei tipi.
     */
    protected $casts = [
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
        'base_price' => 'integer',       // Centesimi: 890 = 8,90 EUR
        'discount_price' => 'integer',   // Centesimi scontati (null = nessuno sconto)
        'show_discount' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Aggiunge automaticamente effective_price e discount_percent alla risposta JSON
    protected $appends = ['effective_price', 'discount_percent'];

    // Scope: filtra solo le fasce di peso
    public function scopeWeight($query) { return $query->where('type', 'weight'); }

    // Scope: filtra solo le fasce di volume
    public function scopeVolume($query) { return $query->where('type', 'volume'); }

    /**
     * Prezzo effettivo: se c'e' uno sconto attivo, usa il prezzo scontato.
     * Altrimenti usa il prezzo pieno. Sempre in centesimi.
     */
    public function getEffectivePriceAttribute(): int
    {
        // discount_price must be > 0 to be a real discount.
        // 0 means "not configured" — use base_price to avoid accidental free shipments.
        if ($this->discount_price !== null && $this->discount_price > 0) {
            return (int) $this->discount_price;
        }
        return (int) $this->base_price;
    }

    /**
     * Percentuale di sconto calcolata: se discount_price < base_price -> sconto positivo.
     * Se discount_price >= base_price o null -> null (nessuno sconto da mostrare).
     * Es: base_price=1190, discount_price=890 -> sconto = 25%
     */
    public function getDiscountPercentAttribute(): ?int
    {
        if ($this->discount_price === null || $this->base_price <= 0) {
            return null;
        }

        if ($this->discount_price >= $this->base_price) {
            return null;
        }

        return (int) round((1 - $this->discount_price / $this->base_price) * 100);
    }
}
