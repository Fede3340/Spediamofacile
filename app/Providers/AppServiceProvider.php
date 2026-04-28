<?php

namespace App\Providers;

use App\Models\BillingAddress;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\InvoiceArchive;
use App\Models\Order;
use App\Models\Package;
use App\Models\ProRequest;
use App\Models\ReferralUsage;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserNotification;
use App\Models\WalletMovement;
use App\Models\WithdrawalRequest;
use App\Policies\BillingAddressPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\CouponPolicy;
use App\Policies\InvoiceArchivePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PackagePolicy;
use App\Policies\ProRequestPolicy;
use App\Policies\ReferralUsagePolicy;
use App\Policies\ServicePolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserAddressPolicy;
use App\Policies\UserNotificationPolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletMovementPolicy;
use App\Policies\WithdrawalRequestPolicy;
use App\Services\CartService;
use App\Services\StripeConfigService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // CartService singleton — shared cart logic (pricing, dedup, merge).
        $this->app->singleton(CartService::class);

        // SVC-02: StripeClient singleton — avoids creating a new SDK instance
        // on every API call.  The secret key is resolved once via
        // StripeConfigService (DB -> .env fallback).
        $this->app->singleton(StripeClient::class, function ($app) {
            $secret = $app->make(StripeConfigService::class)->getSecret();

            // Evitiamo crash di bootstrap quando Stripe non e' configurato:
            // i servizi applicativi controllano gia' `isConfigured()` prima di usare
            // davvero l'API, quindi qui ci basta un client valido per non rompere
            // endpoint non-Stripe come saldo wallet o lettura carte.
            return new StripeClient($secret ?: 'sk_test_spedizionefacile_disabled');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SEC-01: registrazione esplicita delle Policies (best practice Laravel 11).
        // Evita di affidarsi al solo auto-discovery di AuthServiceProvider e garantisce
        // che Gate::authorize/authorizeResource risolvano correttamente i modelli.
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(UserAddress::class, UserAddressPolicy::class);
        Gate::policy(BillingAddress::class, BillingAddressPolicy::class);
        Gate::policy(WalletMovement::class, WalletMovementPolicy::class);
        Gate::policy(WithdrawalRequest::class, WithdrawalRequestPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(ProRequest::class, ProRequestPolicy::class);
        Gate::policy(InvoiceArchive::class, InvoiceArchivePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        // SEC-policies-new: 5 Policy aggiuntive con ownership-check + admin bypass.
        // I controller esistenti NON sono ancora cablati a queste Policy:
        // il refactor controller -> $this->authorize() e' un task separato.
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(ReferralUsage::class, ReferralUsagePolicy::class);
        Gate::policy(UserNotification::class, UserNotificationPolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);

        // SENTRY-OBS-03: invio a Sentry dei job falliti.
        // I job (es. email di conferma ordine, sync BRT) girano in background:
        // senza questo hook, un errore in coda passerebbe SILENZIOSO e
        // scomparirebbe nei log. Con Sentry: alert immediato al team.
        // Guard class_exists: se Sentry non e' installato localmente, zero errori.
        if (class_exists(\Sentry\Laravel\Integration::class)) {
            Queue::failing(function (JobFailed $event): void {
                if (app()->bound('sentry')) {
                    \Sentry\Laravel\Integration::captureUnhandledException($event->exception);
                }
            });
        }
    }
}
