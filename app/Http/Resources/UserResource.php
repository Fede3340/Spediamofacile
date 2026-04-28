<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — controlla i campi User esposti via API.
 *
 * Espone solo i campi safe per il client. NON espone:
 *   - password (hash bcrypt)
 *   - remember_token
 *   - email_verified_at (timestamp interno)
 *   - 2FA secret (se presente)
 *   - stripe_customer_id (PII finanziaria)
 *
 * Nota: $this->whenAdmin() può essere usato in futuro per esporre campi
 * admin-only (lista utenti pannello). Per ora i payload admin includono
 * gli stessi campi base.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'created_at' => $this->created_at?->toIso8601String(),
            // Campi computed safe (helper definiti su User model)
            'is_admin' => $this->isAdmin(),
            'is_pro' => $this->isPro(),
        ];
    }
}
