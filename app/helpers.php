<?php

use App\Models\Location;

if (! function_exists('activeShop')) {
    /**
     * Get the currently active shop for the logged-in user.
     * Returns null for super admin with no shop selected.
     */
    function activeShop(): ?Location
    {
        return app()->bound('active_shop') ? app('active_shop') : null;
    }
}

if (! function_exists('activeShopId')) {
    /**
     * Get the active shop ID, or null for super admin viewing all.
     */
    function activeShopId(): ?int
    {
        return activeShop()?->id;
    }
}

if (! function_exists('isSuperAdmin')) {
    function isSuperAdmin(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
