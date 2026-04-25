<?php
/**
 * FILE: PasswordResetRequestController.php
 * SCOPO: Gestisce la prima fase del recupero password (invio email con token di reset).
 *
 * COSA ENTRA:
 *   - Request con email per sendEmail
 *
 * COSA ESCE:
 *   - JSON con success e message generico per sendEmail (HTTP 200)
 *   - La risposta resta uguale anche se email non esiste (anti-enumerazione)
 *
 * CHIAMATO DA:
 *   - routes/api.php — POST /api/reset-password
 *   - nuxt: pages/recupera-password.vue
 *
 * EFFETTI COLLATERALI:
 *   - Database: crea/aggiorna record in password_reset_tokens (email, token hashato, created_at)
 *   - Email: invia ResetPasswordEmail con token in chiaro e email all'utente
 *
 * VINCOLI:
 *   - Il token viene salvato nel DB hashato con Hash::make (per sicurezza)
 *   - Il token in chiaro viene inviato via email all'utente (64 caratteri, Str::random)
 *   - Se l'utente richiede un nuovo reset, il vecchio token viene sovrascritto
 *   - Non riveliamo se l'email esiste o meno nella risposta di errore (anti-enumerazione)
 *     Nota: attualmente restituisce 404 se non trovata — valutare se cambiare per sicurezza
 *
 * ERRORI TIPICI:
 *   - L'invio email puo' fallire se il server SMTP non e' configurato
 *
 * PUNTI DI MODIFICA SICURI:
 *   - Per cambiare la lunghezza del token: modificare Str::random(64) in createToken()
 *   - Per cambiare il template email: modificare app/Mail/ResetPasswordEmail.php
 *
 * COLLEGAMENTI:
 *   - ChangePasswordController.php — seconda fase: verifica token e cambia la password
 *   - app/Mail/ResetPasswordEmail.php — template dell'email con link di reset
 *   - pages/recupera-password.vue — pagina frontend di recupero password
 */

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\ResetPasswordEmail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
class PasswordResetRequestController extends Controller
{
    /**
     * sendEmail -- Anti-enumerazione email (Sprint 6.4, OWASP Auth Cheatsheet).
     *
     * Risponde SEMPRE 200 con messaggio generico identico, indipendentemente
     * dall'esistenza dell'email. Normalizza il tempo di risposta con un jitter
     * casuale (100-300 ms) per prevenire timing attack: senza il jitter il
     * ramo "utente esiste" impiega ~80-200 ms in piu' a causa di Hash::make,
     * query DB ed invio mail, permettendo all'attaccante di distinguere.
     *
     * Ref: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#authentication-responses
     */
    public function sendEmail(\App\Http\Requests\ForgotPasswordRequest $request) {
        $startedAt = microtime(true);

        // Anti-enumerazione: rispondiamo sempre allo stesso modo, ma inviamo
        // l'email solo se l'account esiste davvero.
        if ($this->validateEmail($request->email)) {
            $this->send($request->email);

            // Log di audit: registriamo SOLO gli invii reali (email esistenti),
            // mai i tentativi verso email non registrate (evita di creare un
            // oracolo via log e rispetta la minimizzazione GDPR).
            Log::info('Password reset email dispatched.', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);
        }

        // Normalizzazione del tempo di risposta: attendiamo che siano passati
        // almeno ~200-300 ms dall'inizio della richiesta. Cosi' sia il ramo
        // "utente esiste" che il ramo "utente non esiste" terminano nello
        // stesso range temporale (diff target < 50 ms).
        $this->normalizeResponseTime($startedAt);

        return $this->successResponse();
    }

    /**
     * Livella la durata della richiesta al bucket di jitter 200-300 ms.
     * Usa usleep() perche' gli sleep Laravel (sleep()) operano a granularita'
     * secondo. random_int garantisce entropia criptograficamente sicura.
     */
    private function normalizeResponseTime(float $startedAt): void
    {
        $targetMicroseconds = random_int(200_000, 300_000); // 200-300 ms
        $elapsedMicroseconds = (int) ((microtime(true) - $startedAt) * 1_000_000);
        $remaining = $targetMicroseconds - $elapsedMicroseconds;

        if ($remaining > 0) {
            usleep($remaining);
        }
    }

    // Genera il token e invia l'email di recupero password all'utente
    public function send($email) {
        // Creiamo un codice segreto (token) per questo reset
        $token = $this->createToken($email);

        // Inviamo l'email con il token all'utente.
        // Async via queue: la response 200 all'utente parte subito (no enumerazione
        // basata su latenza SMTP) e l'invio viene gestito dal worker.
        Mail::to($email)->queue(new ResetPasswordEmail($token, $email));
    }

    // Crea un nuovo token segreto per il reset della password
    // Se l'utente aveva gia' richiesto un reset prima, aggiorna il token esistente
    public function createToken($email) {
        // Controlliamo se esiste gia' un token per questa email
        $oldToken = DB::table('password_reset_tokens')->where('email', $email)->first();

        // Generiamo un codice casuale di 64 caratteri
        $token = Str::random(64);
        // Lo criptiamo prima di salvarlo nel database (per sicurezza)
        $hashedToken = Hash::make($token);

        if ($oldToken) {
            // Se c'era gia' un token, lo aggiorniamo con il nuovo
            $this->updateToken($hashedToken, $email);
        }
        else {
            // Se non c'era, ne creiamo uno nuovo
            $this->saveToken($hashedToken, $email);
        }

        // Restituiamo il token in chiaro (verra' inserito nel link dell'email)
        return $token;
    }


    // Salva un nuovo token nel database
    public function saveToken($token, $email) {
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
    }

    // Aggiorna un token esistente nel database con uno nuovo
    public function updateToken($token, $email) {
        DB::table('password_reset_tokens')
                ->where('email', $email)
                ->update([
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
    }


    // Controlla se un'email e' registrata nel database degli utenti
    // Restituisce vero (true) se l'email esiste, falso (false) se non esiste
    public function validateEmail($email) {
        return User::where('email', $email)->exists();
    }

    // Risposta di successo quando l'email di recupero e' stata inviata correttamente
    // NOTA: la risposta e' volutamente generica per non rivelare se l'email esiste.
    // Il messaggio usa la forma condizionale "Se l'email e' registrata..." come da
    // raccomandazione OWASP Auth Cheatsheet e pattern Stripe/Auth0/Google.
    public function successResponse() {
        return response()->json([
            'success' => true,
            'message' => 'Se l\'email è registrata riceverai un link di reset entro pochi minuti.',
        ], Response::HTTP_OK);
    }
}
