<?php
namespace App\Models;

use App\Models\Order;
use App\Models\Package;
use App\Models\UserAddress;
use App\Models\UserNotificationPreference;
use App\Models\WalletMovement;
use App\Models\ReferralUsage;
use App\Models\ProRequest;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Campi compilabili dall'esterno (mass assignment).
     * Sono i dati che possono essere inseriti o modificati quando si crea/aggiorna un utente.
     * E' una protezione di Laravel: solo questi campi possono essere scritti in massa,
     * cosi' nessuno puo' modificare campi sensibili (come il ruolo) senza autorizzazione.
     */
    protected $fillable = [
        'name',                          // Nome dell'utente
        'surname',                       // Cognome dell'utente
        'email',                         // Indirizzo email (usato anche per il login)
        'telephone_number',              // Numero di telefono libero (input italiano storico)
        'phone_number',                  // F08 SMS: numero in formato E.164 (es. +393331234567)
        'phone_number_verified_at',      // F08 SMS: timestamp verifica OTP (futuro)
        'password',                      // Password (viene salvata criptata)
        'identifier',                    // Identificativo univoco dell'utente
        'email_verified_at',             // Data e ora in cui l'email e' stata verificata
        'verification_code',             // Codice di verifica temporaneo per il login
        'verification_code_expires_at',  // Scadenza del codice di verifica
        'user_type',                     // Tipo account: "privato" o "commerciante"
        'avatar',                        // URL dell'avatar (da Google)
        'privacy_accepted_at',           // Data accettazione privacy (GDPR Art. 7)
        // SICUREZZA: i seguenti campi NON sono in $fillable (assegnare con $user->campo = valore):
        // - role: ruolo utente (User, Partner Pro, Admin)
        // - stripe_account_id: ID account Stripe
        // - customer_id: ID cliente Stripe
        // - google_id, facebook_id, apple_id: ID provider OAuth
        // - referral_code: codice referral personale (generato solo per Partner Pro,
        //   assegnazione esplicita dopo validazione per prevenire mass-assignment)
        // - referred_by: codice referral di chi ha invitato l'utente; va assegnato
        //   esplicitamente SOLO dopo aver validato che appartenga a un Partner Pro
        //   reale (altrimenti un attaccante puo' falsificarsi l'attribuzione referral)
    ];

    /**
     * Campi nascosti nelle risposte JSON.
     * Quando i dati dell'utente vengono inviati al frontend (al browser),
     * questi campi NON vengono inclusi per motivi di sicurezza.
     * Ad esempio, la password non deve mai essere visibile.
     */
    protected $hidden = [
        'password',
        'updated_at',
        'remember_token',
        'verification_code',
        'verification_code_expires_at',
        'stripe_account_id',
        'customer_id',
        'google_id',
        'facebook_id',
        'apple_id',
    ];

    /**
     * Conversioni automatiche dei tipi di dato.
     * Laravel converte automaticamente questi campi nel tipo giusto:
     * - Le date vengono trasformate in oggetti Carbon (per gestire facilmente le date)
     * - La password viene automaticamente criptata quando viene salvata
     *
     * SICUREZZA — ENCRYPTION AT REST (Sprint 6.1, BLOCKER GO-LIVE):
     * I campi `stripe_account_id` e `customer_id` sono segreti Stripe Connect/Customer
     * ad alto valore. Se il database dovesse essere esfiltrato (dump, backup rubato,
     * SQL injection, ex-dipendente), un attaccante potrebbe mappare i nostri Partner
     * Pro ai loro account Stripe reali e tentare attacchi mirati (phishing, social
     * engineering verso Stripe support, correlazione con altri dati).
     *
     * Il cast 'encrypted' applica AES-256-CBC con IV random per record usando
     * APP_KEY, cosicche' il valore sul disco non sia mai leggibile senza la chiave.
     * Trasparente per il codice applicativo: $user->stripe_account_id restituisce
     * il plaintext, $user->stripe_account_id = 'acct_...' lo cifra al save.
     *
     * ATTENZIONE — Query WHERE su colonne cifrate:
     * Con cast 'encrypted' l'IV e' random ad ogni cifratura, quindi
     * User::where('stripe_account_id', $id) NON funziona (ciphertexts diversi).
     * Per lookup inverso (es. webhook Stripe `account.updated`) usare scan lato
     * applicazione sugli utenti Pro, vedi StripeWebhookController::findUserByStripeAccountId().
     *
     * Rotazione APP_KEY: se APP_KEY cambia, ruotarla con `php artisan key:rotate`
     * (Laravel 11+) o rigenerare i valori cifrati con lo script di backfill.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'phone_number_verified_at' => 'datetime',
            'password' => 'hashed',
            'stripe_account_id' => 'encrypted',
            'customer_id' => 'encrypted',
        ];
    }

    /* protected static function booted()
    {
        static::creating(function ($user) {
            $user->identifier = (string) Str::uuid();
        });
    } */

    // Controlla se l'utente e' un amministratore del sito
    // Restituisce true (vero) se il ruolo dell'utente e' "Admin"
    public function isAdmin(): bool {
        return $this->role === 'Admin';
    }

    /* ===== SCOPES — Query predefinite per ruoli e stati comuni ===== */

    public function scopeAdmins($query)
    {
        return $query->where('role', 'Admin');
    }

    public function scopePartnerPro($query)
    {
        return $query->where('role', 'Partner Pro');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    // Relazione: un utente ha MOLTI indirizzi salvati nella sua rubrica
    // Esempio: casa, ufficio, magazzino...
    public function addresses(): HasMany {
        return $this->hasMany(UserAddress::class);
    }

    /* public function cart() {
        return $this->belongsToMany(Package::class, 'cart_user')
                    ->withPivot('quantity')
                    ->withTimestamps();
    } */

    /* public function cart() {
        return $this->hasMany(CartUser::class); // carrello dell'utente
    } */

    // Relazione: un utente ha MOLTI pacchi configurati
    // Sono i pacchi che l'utente ha preparato per la spedizione
    public function packages(): HasMany {
        return $this->hasMany(Package::class);
    }

    // Relazione: un utente ha MOLTI ordini
    // Ogni volta che l'utente paga una spedizione, viene creato un ordine
    public function orders(): HasMany {
        return $this->hasMany(Order::class);
    }

    // Controlla se l'utente e' un Partner Pro
    // I Partner Pro hanno un codice referral e guadagnano commissioni
    public function isPro(): bool {
        return $this->role === 'Partner Pro';
    }

    // Relazione: un utente ha MOLTI movimenti nel portafoglio
    // I movimenti possono essere ricariche (credit) o pagamenti (debit)
    public function walletMovements(): HasMany {
        return $this->hasMany(WalletMovement::class);
    }

    // Relazione: un utente Pro ha MOLTI utilizzi del suo codice referral
    // Ogni volta che qualcuno usa il suo codice, viene registrato qui
    public function referralUsagesAsPro(): HasMany {
        return $this->hasMany(ReferralUsage::class, 'pro_user_id');
    }

    // Relazione: un utente ha MOLTE richieste di prelievo delle commissioni guadagnate
    public function withdrawalRequests(): HasMany {
        return $this->hasMany(WithdrawalRequest::class);
    }

    // Relazione: un utente ha MOLTE richieste per diventare Partner Pro
    public function proRequests(): HasMany {
        return $this->hasMany(ProRequest::class);
    }

    /**
     * F08/F09 - preferenze notifiche (relazione 1:1, creata on-demand).
     */
    public function notificationPreference()
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    /**
     * Calcola il saldo del portafoglio dell'utente.
     * Prende tutti i movimenti confermati, somma le ricariche (credit)
     * e sottrae i pagamenti (debit). Il risultato e' il saldo disponibile.
     */
    public function walletBalance(): float {
        $credits = $this->walletMovements()
            ->where('status', 'confirmed')
            ->where('type', 'credit')
            ->where(function ($query) {
                $query->whereNull('source')
                    ->orWhereNotIn('source', ['commission', 'withdrawal']);
            })
            ->sum('amount');
        $debits = $this->walletMovements()
            ->where('status', 'confirmed')
            ->where('type', 'debit')
            ->where(function ($query) {
                $query->whereNull('source')
                    ->orWhereNotIn('source', ['commission', 'withdrawal']);
            })
            ->sum('amount');
        return round($credits - $debits, 2);
    }

    /**
     * Calcola il saldo delle commissioni guadagnate dall'utente Pro.
     * Prende le commissioni confermate e sottrae i prelievi gia' approvati o completati.
     * Se esiste una richiesta pending, la considera come saldo gia' riservato.
     * Il risultato e' quanto puo' ancora prelevare.
     */
    public function commissionBalance(): float {
        $earned = $this->referralUsagesAsPro()
            ->where('status', 'confirmed')
            ->sum('commission_amount');
        $reserved = $this->withdrawalRequests()
            ->where('status', 'pending')
            ->sum('amount');
        $withdrawn = $this->withdrawalRequests()
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
        return round($earned - $reserved - $withdrawn, 2);
    }

    /**
     * Azioni automatiche quando viene creato un nuovo utente.
     * Se l'utente e' un Partner Pro e non ha ancora un codice referral,
     * gliene viene generato uno casuale di 8 caratteri (es. "AB3K9XZ2").
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if ($user->role === 'Partner Pro' && empty($user->referral_code)) {
                $user->referral_code = strtoupper(Str::random(8));
            }
        });
    }
}
