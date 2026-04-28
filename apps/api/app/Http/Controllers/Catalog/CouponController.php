<?php
namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;

use App\Http\Requests\CalculateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Services\DiscountPreviewService;

class CouponController extends Controller
{
    /*
     * Boundary note:
     * - questo controller serve solo per preview/validazione del codice nel checkout;
     * - il source of truth monetario dell'ordine non nasce qui;
     * - per la parte economica post-ordine vedere ReferralRewardController.
     */

    // Calcola lo sconto da applicare in base al codice inserito dall'utente.
    // Validazione delegata a CalculateCouponRequest (testabile + riusabile).
    public function calculateCoupon(CalculateCouponRequest $request, DiscountPreviewService $discountPreviewService)
    {
        $data = $request->validated();

        $couponCode = strtoupper(trim($data['coupon']));   // Il codice inserito dall'utente
        $total = $data['total'];          // Il totale del carrello in euro

        // PRIMA controlliamo se e' un coupon classico (creato dall'admin)
        // Cerchiamo nel database un coupon con questo codice che sia ancora attivo
        $coupon = Coupon::where('code', $couponCode)->where('active', true)->first();

        if ($coupon) {
            // SEC-NEW-07: Verifica anti-abuso (scadenza, limiti globali e per-utente)
            $userId = auth()->id();
            [$isValid, $errorMessage] = $coupon->validateForUser($userId);

            if (! $isValid) {
                return response()->json(['error' => $errorMessage], 422);
            }

            return response()->json(
                $discountPreviewService->buildCouponPreview($coupon, (float) $total)
            );
        }

        // POI controlliamo se e' un codice referral di un Partner Pro
        // I codici referral sono codici di 8 caratteri assegnati ai Partner Pro
        $proUser = $discountPreviewService->resolveReferralPartner($couponCode);

        if ($proUser) {
            $buyer = auth()->user();

            // L'utente non puo' usare il proprio codice referral su se stesso
            if ($proUser->id === $buyer->id) {
                return response()->json([
                    'error' => 'Non puoi usare il tuo stesso codice referral.'
                ], 422);
            }

            return response()->json(
                $discountPreviewService->buildReferralPreview($proUser, (float) $total, $couponCode)
            );
        }

        // Se il codice non corrisponde ne' a un coupon ne' a un codice referral, e' non valido
        return response()->json([
            'error' => 'Codice non valido'
        ], 404);
    }
}
