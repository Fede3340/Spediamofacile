<?php

/**
 * ReferralCodeController -- Gestione codici referral: generazione, validazione, salvataggio, sconto.
 *
 * Estratto da ReferralController: gestisce myCode, validate, storeReferral, myDiscount.
 * Queste funzioni riguardano la gestione del codice referral stesso (non i guadagni).
 */

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use App\Services\DiscountPreviewService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralCodeController extends Controller
{
    /**
     * Mostra il codice referral del Partner Pro con le statistiche dei guadagni.
     * Include anche i link pronti per condividere il codice (link diretto e WhatsApp).
     */
    public function myCode(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->isPro()) {
            return response()->json(['message' => 'Solo gli account Pro possono avere un codice referral.'], 403);
        }

        if (! $user->referral_code) {
            $user->referral_code = strtoupper(Str::random(8));
            $user->save();
        }

        $totalEarnings = $user->referralUsagesAsPro()->where('status', 'confirmed')->sum('commission_amount');
        $totalUsages = $user->referralUsagesAsPro()->count();

        $baseUrl = config('app.frontend_url', config('app.url'));
        $referralLink = $baseUrl . '?ref=' . $user->referral_code;
        $whatsappMessage = urlencode("Spedisci con SpediamoFacile e ottieni il 5% di sconto! Usa il mio codice: {$user->referral_code} oppure registrati da qui: {$referralLink}");
        $whatsappLink = "https://wa.me/?text={$whatsappMessage}";

        return response()->json([
            'referral_code' => $user->referral_code,
            'total_earnings' => round($totalEarnings, 2),
            'total_usages' => $totalUsages,
            'referral_link' => $referralLink,
            'whatsapp_link' => $whatsappLink,
        ]);
    }

    /**
     * Verifica se un codice referral e' valido.
     * Usato dal frontend per mostrare un messaggio di conferma prima di procedere al pagamento.
     */
    public function validate(\App\Http\Requests\ReferralCodeRequest $request, DiscountPreviewService $discountPreviewService): JsonResponse
    {
        $data = $request->validated();

        $proUser = $discountPreviewService->resolveReferralPartner($data['code']);

        if (! $proUser) {
            return response()->json(['valid' => false, 'message' => 'Codice non valido.'], 404);
        }

        if ($proUser->id === auth()->id()) {
            return response()->json(['valid' => false, 'message' => 'Non puoi usare il tuo stesso codice.'], 422);
        }

        return response()->json([
            'valid' => true,
            'discount_percent' => $discountPreviewService->referralDiscountPercent(),
            'pro_name' => $proUser->name,
        ]);
    }

    /**
     * Salva il codice referral sull'account dell'utente.
     * Chiamato quando un utente si registra tramite un link referral (es. ?ref=ABC12345)
     * o quando inserisce manualmente un codice referral.
     */
    public function storeReferral(\App\Http\Requests\ReferralCodeRequest $request, DiscountPreviewService $discountPreviewService): JsonResponse
    {
        $data = $request->validated();

        $code = strtoupper($data['code']);

        $proUser = $discountPreviewService->resolveReferralPartner($code);

        if (! $proUser) {
            return response()->json(['message' => 'Codice referral non valido.'], 404);
        }

        $user = auth()->user();

        if ($proUser->id === $user->id) {
            return response()->json(['message' => 'Non puoi usare il tuo stesso codice.'], 422);
        }

        if ($user->referred_by) {
            if (strtoupper($user->referred_by) === $code) {
                return response()->json([
                    'success' => true,
                    'referred_by' => $code,
                    'discount_percent' => $discountPreviewService->referralDiscountPercent(),
                    'pro_name' => $proUser->name,
                ]);
            }

            return response()->json([
                'message' => 'Hai gia un codice referral associato e non puo essere sostituito.',
            ], 409);
        }

        $user->referred_by = $code;
        $user->save();

        return response()->json([
            'success' => true,
            'referred_by' => $code,
            'discount_percent' => $discountPreviewService->referralDiscountPercent(),
            'pro_name' => $proUser->name,
        ]);
    }

    /**
     * Mostra lo sconto referral attivo dell'utente.
     * Se l'utente e' stato invitato da un Partner Pro, restituisce le informazioni sullo sconto.
     */
    public function myDiscount(DiscountPreviewService $discountPreviewService): JsonResponse
    {
        $user = auth()->user();

        if (! $user->referred_by) {
            return response()->json([
                'has_discount' => false,
            ]);
        }

        $proUser = $discountPreviewService->resolveReferralPartner($user->referred_by);

        if (! $proUser) {
            return response()->json([
                'has_discount' => false,
            ]);
        }

        return response()->json(
            $discountPreviewService->buildReferralDiscountInfo($proUser, $user->referred_by)
        );
    }
}
