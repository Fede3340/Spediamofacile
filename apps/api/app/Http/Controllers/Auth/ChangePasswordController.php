<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdatePasswordRequest;
use Symfony\Component\HttpFoundation\Response;
class ChangePasswordController extends Controller
{
    // Funzione principale che gestisce tutto il processo di cambio password
    // Verifica il token e, se valido, procede con il cambio
    public function passwordResetProcess(UpdatePasswordRequest $request) {
        $tokenQuery = $this->updatePasswordRow($request);
        return ($tokenQuery && $tokenQuery->count() > 0) ? $this->resetPassword($request) : $this->tokenNotFoundError();
    }

    // Verifica se il token (codice segreto) inviato dall'utente corrisponde a quello salvato nel database
    // Questo serve per assicurarsi che chi sta cambiando la password sia davvero il proprietario dell'email
    // Restituisce il query builder se valido, null se non valido
    private function updatePasswordRow($request) {
        // Cerchiamo nel database il record con l'email dell'utente
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        // Confrontiamo il token inviato con quello salvato (Hash::check verifica la corrispondenza)
        if ($record && Hash::check($request->resetToken, $record->token)) {
            return DB::table('password_reset_tokens')->where([
                'email' => $request->email,
            ]);
        }

        return null;
    }

    // Risposta di errore quando il token non e' valido o non esiste
    private function tokenNotFoundError() {
        return response()->json([
            'success' => false,
            'message' => 'L\'indirizzo email o il link per reimpostare la password non sono corretti.'
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Esegue il vero cambio di password dopo aver verificato che il token sia valido
    private function resetPassword($request) {
        // Cerchiamo l'utente tramite la sua email
        $userData = User::whereEmail($request->email)->first();

        // Recuperiamo la data di creazione del token per verificare che non sia scaduto
        $tokenCreatedAt = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->value('created_at');

        if (!$tokenCreatedAt) {
            return $this->tokenNotFoundError();
        }

        // Controlliamo se il token e' scaduto (i token durano 60 minuti)
        $isExpired = Carbon::parse($tokenCreatedAt)->lt(Carbon::now()->subMinutes(60));

        if ($isExpired) {
            // Il link per reimpostare la password e' scaduto
            return response()->json([
                'success' => false,
                'message' => 'Il link per reimpostare la password è scaduto.'
            ], Response::HTTP_UNAUTHORIZED);
        }
        else {

            // Recuperiamo la password attuale dell'utente dal database
            $userPassword = DB::table('users')
                    ->where('email', $request->email)
                    ->value('password');

            $newPassword = $request->password;

            // Controlliamo che la nuova password sia diversa da quella vecchia
            if (Hash::check($newPassword, $userPassword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La nuova password deve essere diversa da quella precedente.'
                ], Response::HTTP_BAD_REQUEST);
            }
            else {
                // Aggiorniamo la password nel database
                // Il cast 'hashed' sul modello User esegue bcrypt automaticamente,
                // quindi NON usiamo bcrypt() qui per evitare doppio hash
                $userData->update([
                    'password' => $request->password
                ]);

                // Eliminiamo il token dal database perche' e' stato usato
                // (ogni token puo' essere usato una sola volta)
                $this->updatePasswordRow($request)->delete();

                // Rispondiamo con un messaggio di successo
                return response()->json([
                    'success' => true,
                    'message' => 'La password è stata modificata con successo.'
                ], Response::HTTP_CREATED);
            }
        }
    }
}
