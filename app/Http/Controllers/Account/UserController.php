<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAvatarUploadRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Security\ImageSanitizer;
use App\Support\CustomResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CONTROLLER UTENTE
 *
 * Questo controller gestisce le operazioni legate al profilo dell'utente.
 * Permette di: modificare i propri dati personali (nome, email, telefono, password),
 * caricare immagini (usate dall'amministratore), e recuperare l'immagine caricata.
 */
class UserController extends Controller
{
    // Permette all'utente di aggiornare i propri dati personali (partial update).
    // Auth + validation centralizzate in UpdateUserRequest (UserPolicy.update).
    // Il cast 'hashed' su User::password si occupa di hashare automaticamente — niente bcrypt esplicito.
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return CustomResponse::setSuccessResponse('Modifica effettuata con successo', Response::HTTP_OK);
    }

    /**
     * Elimina l'account dell'utente autenticato (GDPR Art. 17 — Diritto all'oblio).
     *
     * COSA FA:
     *   1. Verifica che l'utente stia cancellando il PROPRIO account (non quello di un altro)
     *   2. Anonimizza gli ordini (non li cancella: servono per la contabilita' e gli obblighi fiscali)
     *   3. Revoca tutti i token Sanctum (logout da tutti i dispositivi)
     *   4. Cancella i dati personali dell'utente (GDPR soft delete)
     *   5. Invia email di conferma all'indirizzo registrato
     *
     * PERCHE' NON CANCELLIAMO GLI ORDINI:
     *   Gli ordini contengono dati fiscali (importi, date, riferimenti BRT) necessari per
     *   legge (D.P.R. 633/72 — IVA: conservazione 10 anni). Li anonimizziamo invece di eliminarli.
     */
    public function destroy(Request $request, User $user)
    {
        // Controllo di sicurezza: solo l'utente stesso puo' cancellare il proprio account
        if ($user->id !== auth()->user()->id) {
            abort(403, 'Non sei autorizzato a eliminare questo account.');
        }

        $userEmail = $user->email;
        $userName = $user->name;

        DB::transaction(function () use ($user) {
            // 1. Anonimizza gli ordini collegati all'utente.
            //    Scolleghiamo l'user_id invece di cancellare l'ordine per preservare
            //    i dati contabili/fiscali richiesti dalla legge italiana.
            Order::query()
                ->where('user_id', $user->id)
                ->update(['user_id' => null]);

            // 2. Revoca tutti i token Sanctum (logout da tutti i dispositivi)
            $user->tokens()->delete();

            // 3. Cancella i dati personali dell'utente.
            //    Sovrascriviamo i campi con valori anonimi prima del soft delete,
            //    cosi' i dati non sono recuperabili nemmeno dalla tabella deleted.
            $anonymizedId = 'deleted_'.$user->id;
            // forceFill bypassa $fillable: necessario per azzerare anche i campi protetti
            $user->forceFill([
                'name' => 'Utente eliminato',
                'surname' => '',
                'email' => $anonymizedId.'@eliminato.local',
                'telephone_number' => null,
                'password' => Str::random(64),
                'verification_code' => null,
                'verification_code_expires_at' => null,
                'google_id' => null,
                'facebook_id' => null,
                'apple_id' => null,
                'avatar' => null,
                'customer_id' => null,
                'stripe_account_id' => null,
                'referral_code' => null,
                'referred_by' => null,
            ])->save();

            // 4. Soft delete dell'utente (segna come eliminato nel DB)
            $user->delete();
        });

        // 5. Invia email di conferma all'indirizzo originale (fuori dalla transazione)
        try {
            Mail::raw(
                "Gentile {$userName},\n\n".
                "Il tuo account SpedizioneFacile e' stato eliminato con successo.\n".
                "I tuoi dati personali sono stati rimossi dai nostri sistemi.\n\n".
                'Se non hai richiesto tu questa eliminazione, contatta immediatamente '.
                "il nostro supporto all'indirizzo info@spedizionefacile.it\n\n".
                'SpedizioneFacile',
                function ($message) use ($userEmail, $userName) {
                    $message->to($userEmail, $userName)
                        ->subject('Account eliminato — SpedizioneFacile');
                }
            );
        } catch (\Exception $e) {
            // L'email di conferma e' best-effort: non blocchiamo l'eliminazione se fallisce
            Log::warning('Account deletion confirmation email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('User account deleted (GDPR Art. 17)', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Account eliminato con successo. Riceverai una email di conferma.',
        ]);
    }

    // Questa funzione permette di caricare un'immagine (usata per l'immagine dell'admin).
    // Sprint 6.7 security hardening: validazione tramite AdminAvatarUploadRequest
    // (extension + real MIME + dimensions + magic byte), sanitizzazione tramite
    // ImageSanitizer (hash filename, EXIF strip, dir whitelist).
    public function uploadFile(AdminAvatarUploadRequest $request, ImageSanitizer $sanitizer)
    {
        $path = $sanitizer->sanitizeAndStore(
            $request->file('admin_image'),
            'attach',
            'public'
        );

        return response()->json([
            'success' => true,
            'message' => 'File caricato con successo',
            'admin_image' => $path,
        ]);
    }

    // Questa funzione recupera l'ultima immagine caricata dall'admin
    // Cerca nella cartella "attach" e restituisce l'URL dell'ultimo file trovato
    public function getAdminImage()
    {
        // Prima controlla se l'admin ha impostato un'immagine dalle impostazioni
        $settingUrl = Setting::get('homepage_image_url', '');
        if ($settingUrl) {
            return response()->json(['image_url' => $settingUrl]);
        }

        // Fallback: leggiamo tutti i file presenti nella cartella "attach"
        $files = Storage::disk('public')->files('attach');

        // Se non ci sono file, restituiamo una stringa vuota
        if (empty($files)) {
            return response()->json(['image_url' => '']);
        }

        // Prendiamo l'ultimo file caricato dalla lista
        $lastFile = collect($files)->last();

        // Costruiamo l'indirizzo web (URL) per accedere all'immagine
        $url = asset('storage/'.$lastFile);

        return response()->json(['image_url' => $url]);
    }
}
