<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $name
 * @property string|null $address
 * @property string|null $address_number
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $province
 * @property string|null $telephone_number
 * @property-read Collection<int, Package> $packagesAsOrigin
 * @property-read Collection<int, Package> $packagesAsDestination
 */
class PackageAddress extends Model
{
    use HasFactory;

    /**
     * Campi compilabili dall'esterno.
     * Tutti i dettagli necessari per identificare un indirizzo completo.
     */
    protected $fillable = [
        'type',                    // Tipo di indirizzo (es. "privato", "azienda")
        'name',                    // Nome completo del mittente/destinatario
        'additional_information',  // Informazioni aggiuntive (es. "secondo piano", "presso...")
        'address',                 // Via/piazza/corso
        'number_type',             // Tipo di numero civico (es. "civico", "km")
        'address_number',          // Numero civico
        'intercom_code',           // Codice citofono (utile per il corriere)
        'country',                 // Nazione (es. "Italia")
        'city',                    // Citta'
        'postal_code',             // CAP - Codice di Avviamento Postale
        'province',                // Sigla provincia (es. "MI", "RM", "NA")
        'telephone_number',        // Numero di telefono
        'email',                   // Indirizzo email
    ];

    // Relazione: questo indirizzo e' usato come PARTENZA da molti pacchi
    public function packagesAsOrigin(): HasMany
    {
        return $this->hasMany(Package::class, 'origin_address_id');
    }

    // Relazione: questo indirizzo e' usato come DESTINAZIONE da molti pacchi
    public function packagesAsDestination(): HasMany
    {
        return $this->hasMany(Package::class, 'destination_address_id');
    }
}
