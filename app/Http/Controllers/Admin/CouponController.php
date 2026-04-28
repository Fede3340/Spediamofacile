<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    // Lista tutti i coupon
    public function index(): JsonResponse
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $coupons]);
    }

    // Crea un nuovo coupon
    public function store(\App\Http\Requests\StoreCouponRequest $request): JsonResponse
    {
        $data = $request->validated();

        $coupon = Coupon::create([
            'code' => strtoupper($data['code']),
            'percentage' => $data['percentage'],
            'active' => $data['active'] ?? true,
            'expires_at' => $data['expires_at'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $coupon], 201);
    }

    // Aggiorna un coupon
    public function update(\App\Http\Requests\UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        $coupon->update($data);

        return response()->json(['success' => true, 'data' => $coupon->fresh()]);
    }

    // Elimina un coupon
    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return response()->json(['success' => true, 'message' => 'Coupon eliminato.']);
    }
}
