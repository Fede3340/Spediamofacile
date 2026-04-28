<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'name',              // Nome di chi scrive
        'surname',           // Cognome di chi scrive
        'email',             // Email di chi scrive (per poter rispondere)
        'subject',           // Oggetto della richiesta (contatto pubblico o ticket assistenza)
        'telephone_number',  // Telefono di chi scrive
        'address',           // Indirizzo di chi scrive
        'message',           // Il testo del messaggio
        'read_at',           // Data e ora in cui un admin ha letto il messaggio
    ];

    // Converte automaticamente la data di lettura in un oggetto data
    protected $casts = [
        'read_at' => 'datetime',
    ];
}
