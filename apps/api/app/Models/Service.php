<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $service_data
 * @property-read Collection<int, Package> $packages
 */
class Service extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'service_type',  // Tipo di servizio (es. "standard", "express", "economy")
        'time',          // Orario di ritiro scelto
        'date',          // Data di ritiro scelta
        'service_data',  // Dati aggiuntivi servizi (es. importo contrassegno) in formato JSON
    ];

    protected $casts = [
        'service_data' => 'array',
    ];

    // Relazione: un servizio e' usato da MOLTI pacchi
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'service_id');
    }
}
