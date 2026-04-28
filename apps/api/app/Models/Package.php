<?php
namespace App\Models;

use App\Models\Traits\HasPrice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User|null $user
 * @property-read PackageAddress|null $originAddress
 * @property-read PackageAddress|null $destinationAddress
 * @property-read Service|null $service
 */
class Package extends Model
{
    // Usa il trait HasPrice per gestire automaticamente la formattazione del prezzo
    use HasFactory, HasPrice;

    /**
     * Campi compilabili dall'esterno.
     * Sono le informazioni del pacco che possono essere inserite o modificate.
     */
    protected $fillable = [
        'package_type',           // Tipo di pacco (es. "busta", "scatola", "pallet")
        'quantity',               // Quanti pacchi uguali a questo
        'weight',                 // Peso del pacco in kg
        'first_size',             // Prima dimensione (lunghezza) in cm
        'second_size',            // Seconda dimensione (larghezza) in cm
        'third_size',             // Terza dimensione (altezza) in cm
        'weight_price',           // Prezzo calcolato in base al peso
        'volume_price',           // Prezzo calcolato in base al volume (dimensioni)
        'single_price',           // Prezzo finale per singolo pacco
        'origin_address_id',      // ID dell'indirizzo di partenza
        'destination_address_id', // ID dell'indirizzo di destinazione
        'service_id',             // ID del servizio di spedizione scelto
        'user_id',                // ID dell'utente che spedisce
        'content_description',    // Descrizione del contenuto (es. "Elettronica")
    ];

    // Converte automaticamente i campi nei tipi corretti
    protected $casts = [
        'weight'       => 'float',
        'first_size'   => 'float',
        'second_size'  => 'float',
        'third_size'   => 'float',
        'weight_price' => 'float',
        'volume_price' => 'float',
        'single_price' => 'integer',  // centesimi: 1190 = 11,90 EUR
        'quantity'     => 'integer',
    ];

    // Relazione: ogni pacco appartiene a UN utente
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relazione: ogni pacco appartiene a UN indirizzo di partenza (foreign key origin_address_id)
    public function originAddress(): BelongsTo
    {
        return $this->belongsTo(PackageAddress::class, 'origin_address_id');
    }

    // Relazione: ogni pacco appartiene a UN indirizzo di destinazione (foreign key destination_address_id)
    public function destinationAddress(): BelongsTo
    {
        return $this->belongsTo(PackageAddress::class, 'destination_address_id');
    }

    // Relazione: ogni pacco appartiene a UN servizio di spedizione (foreign key service_id)
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
