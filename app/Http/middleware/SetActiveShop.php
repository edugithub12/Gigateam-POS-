<?php

namespace App\Http\Middleware;

use App\Models\Location;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetActiveShop
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Super admin: respect session selection or leave null (all shops)
        if ($user->isSuperAdmin()) {
            $shopId = session('active_shop_id');
            $shop   = $shopId ? Location::find($shopId) : null;
            app()->instance('active_shop', $shop);
            return $next($request);
        }

        // Regular user: use session shop if they can access it,
        // otherwise fall back to their primary location
        $shopId = session('active_shop_id');

        if ($shopId && $user->canAccessLocation((int) $shopId)) {
            $shop = Location::find($shopId);
        } else {
            $shop = $user->primaryLocation();
            if ($shop) {
                session(['active_shop_id' => $shop->id]);
            }
        }

        app()->instance('active_shop', $shop);

        return $next($request);
    }
}
