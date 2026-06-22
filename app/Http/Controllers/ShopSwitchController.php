<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class ShopSwitchController extends Controller
{
    public function __invoke(Request $request, int $shopId)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // shopId = 0 means "All Shops" (super admin only)
        if ($shopId === 0) {
            if (! $user->isSuperAdmin()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            session()->forget('active_shop_id');
            app()->instance('active_shop', null);
            return response()->json(['shop' => null]);
        }

        // Validate the user can access this shop
        if (! $user->canAccessLocation($shopId)) {
            return response()->json(['error' => 'You are not assigned to that shop'], 403);
        }

        $shop = Location::findOrFail($shopId);
        session(['active_shop_id' => $shop->id]);
        app()->instance('active_shop', $shop);

        return response()->json([
            'shop' => ['id' => $shop->id, 'name' => $shop->name],
        ]);
    }
}
