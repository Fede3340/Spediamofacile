<?php

/**
 * SERVICE: AuditLogService
 *
 * F14 (audit BRT 2026-04-18) — Punto unico per scrivere righe di audit.
 *
 * USO:
 *   AuditLogService::log('auth.login', null, ['method' => 'password']);
 *   AuditLogService::log('order.refund', $order, ['amount_cents' => 1500, 'reason' => 'cliente']);
 *   AuditLogService::log('admin.user.role_change', $user, ['from' => 'User', 'to' => 'Admin']);
 *
 * IL METODO log() E' "FAIL-SAFE":
 *   Se la scrittura su DB fallisce non deve mai bloccare l'azione di business
 *   (es. un login non deve fallire perche' la tabella audit_logs e' down).
 *   Logga il fallimento sui log applicativi e prosegue.
 *
 * SANITIZATION: il context viene scandito per rimuovere chiavi sensibili
 * (password, token, secret, *_secret, *_token, authorization).
 */

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /** @var array<int,string> chiavi che vengono mascherate nel context */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password',
        'token', 'access_token', 'refresh_token', 'api_token',
        'secret', 'client_secret',
        'authorization',
        'card_number', 'cvc', 'cvv', 'pan',
    ];

    /**
     * Scrive una riga di audit.
     *
     * @param  string                 $action  slug breve, es. 'auth.login', 'order.create'
     * @param  Model|null             $target  modello target (se applicabile)
     * @param  array<string,mixed>    $context payload extra
     * @param  array<string,mixed>    $opts    override avanzati: 'user_id', 'actor_type', 'ip', 'user_agent'
     */
    public static function log(string $action, ?Model $target = null, array $context = [], array $opts = []): ?AuditLog
    {
        try {
            $request = request();
            $user = $opts['user'] ?? Auth::user();

            $userId = $opts['user_id'] ?? ($user?->id);
            $actorType = $opts['actor_type'] ?? self::resolveActorType($user);

            $ip = $opts['ip'] ?? ($request ? $request->ip() : null);
            $userAgent = $opts['user_agent'] ?? ($request ? mb_substr((string) $request->userAgent(), 0, 512) : null);

            return AuditLog::create([
                'user_id' => $userId,
                'actor_type' => $actorType,
                'action' => $action,
                'target_type' => $target ? class_basename($target) : ($opts['target_type'] ?? null),
                'target_id' => $target?->getKey() ?? ($opts['target_id'] ?? null),
                'ip' => $ip,
                'user_agent' => $userAgent,
                'context' => self::sanitizeContext($context),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail-safe: non blocchiamo mai il flusso applicativo per un errore di logging.
            Log::warning('[AuditLog] scrittura fallita', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private static function resolveActorType(?User $user): string
    {
        if (! $user) {
            return 'guest';
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 'admin';
        }
        return 'user';
    }

    /**
     * Maschera ricorsivamente le chiavi sensibili nel payload.
     *
     * @param  array<string,mixed>  $context
     * @return array<string,mixed>
     */
    private static function sanitizeContext(array $context): array
    {
        $sensitive = self::SENSITIVE_KEYS;

        $walk = static function ($value) use (&$walk, $sensitive) {
            if (! is_array($value)) {
                return $value;
            }
            foreach ($value as $key => $v) {
                $lc = is_string($key) ? mb_strtolower($key) : null;
                if ($lc !== null && (in_array($lc, $sensitive, true) || preg_match('/(secret|token|password)$/i', $lc))) {
                    $value[$key] = '[REDACTED]';
                    continue;
                }
                if (is_array($v)) {
                    $value[$key] = $walk($v);
                }
            }
            return $value;
        };

        return $walk($context);
    }
}
