<?php
namespace App\Services\Brt;

class ErrorTranslator
{
    /**
     * Mappa codice BRT => [messaggio utente, azione suggerita].
     * I codici provengono da executionMessage.code della BRT REST API v1.
     */
    private const ERROR_MAP = [
        -1   => ['Autenticazione fallita: credenziali BRT non valide.',
                  'Verificare BRT_CLIENT_ID e BRT_PASSWORD nel file .env.'],
        -2   => ['Parametri mancanti o non validi nella richiesta.',
                  'Controllare i dati inseriti e riprovare.'],
        -3   => ['Account non abilitato al servizio richiesto.',
                  'Contattare BRT per abilitare il servizio sul proprio account.'],
        -10  => ['Indirizzo destinatario non valido.',
                  'Verificare via, numero civico e localita del destinatario.'],
        -11  => ['CAP destinatario non trovato.',
                  'Verificare che il CAP corrisponda alla citta indicata.'],
        -12  => ['Provincia destinatario non valida.',
                  'Verificare la sigla provincia (es. MI, RM, NA).'],
        -20  => ['Peso non valido o fuori dai limiti consentiti.',
                  'Verificare che il peso sia entro i limiti del servizio scelto.'],
        -21  => ['Dimensioni non valide o fuori dai limiti consentiti.',
                  'Verificare lunghezza, larghezza e altezza del collo.'],
        -30  => ['Servizio non disponibile per questa tratta.',
                  'Provare un servizio diverso o verificare le localita.'],
        -31  => ['Tipo di spedizione non supportato.',
                  'Selezionare un tipo di spedizione valido per il proprio account.'],
        -40  => ['Contrassegno non configurato per questo account.',
                  'Contattare BRT per attivare il servizio contrassegno.'],
        -41  => ['Importo contrassegno non valido.',
                  'Verificare che l\'importo sia positivo e nel formato corretto.'],
        -50  => ['Filiale di partenza non valida.',
                  'Verificare il codice filiale mittente configurato.'],
        -51  => ['Zona di consegna non coperta dal servizio.',
                  'Verificare CAP e localita del destinatario.'],
        -60  => ['Numero massimo di colli superato.',
                  'Ridurre il numero di colli o suddividere in piu spedizioni.'],
        -63  => ['Errore di routing: zona non coperta.',
                  'Verificare che citta, CAP e provincia corrispondano tra loro.'],
        -70  => ['Etichetta non generabile per un problema temporaneo BRT.',
                  'Riprovare tra qualche minuto; se persiste, contattare BRT.'],
        -80  => ['Limite giornaliero di spedizioni raggiunto.',
                  'Attendere il giorno successivo o contattare BRT per ampliare il limite.'],
        -100 => ['Errore interno del server BRT.',
                  'Riprovare tra qualche minuto; se persiste, contattare l\'assistenza BRT.'],
    ];

    /** Codici che rappresentano errori transitori (ha senso riprovare). */
    private const RETRYABLE_CODES = [-70, -100];

    /**
     * Traduce un codice errore BRT in un messaggio italiano leggibile.
     * Per il codice -63 arricchisce il messaggio con i dati dell'indirizzo.
     */
    public function translate(int $code, string $codeDesc, string $message, array $createData): string
    {
        // Per errori di routing, includi i dettagli dell'indirizzo
        if ($code === -63 || stripos($codeDesc, 'ROUTING') !== false) {
            $city     = $createData['consigneeCity'] ?? '?';
            $zip      = $createData['consigneeZIPCode'] ?? '?';
            $province = $createData['consigneeProvinceAbbreviation'] ?? '?';

            return "Errore indirizzo BRT: la citta '{$city}' non corrisponde al CAP '{$zip}' "
                 . "(provincia: {$province}). Verificare che citta, CAP e provincia siano corretti.";
        }

        // Messaggio dalla mappa codici
        if (isset(self::ERROR_MAP[$code])) {
            [$msg, $azione] = self::ERROR_MAP[$code];
            return "{$msg} Suggerimento: {$azione}";
        }

        // Fallback: messaggio BRT originale o generico
        if ($message) {
            return "Errore BRT (codice {$code}, {$codeDesc}): {$message}";
        }

        return "Errore BRT sconosciuto (codice {$code}).";
    }

    /**
     * Indica se l'errore e transitorio e la richiesta puo essere ritentata.
     * Restituisce true per errori temporanei (-70, -100), false altrimenti.
     */
    public function isRetryable(int $code): bool
    {
        return in_array($code, self::RETRYABLE_CODES, true);
    }
}
