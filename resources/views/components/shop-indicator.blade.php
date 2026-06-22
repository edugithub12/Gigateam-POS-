@php
    use App\Models\Location;

    $activeShop  = activeShop();
    $user        = auth()->user();
    $canSwitch   = $user && ($user->isSuperAdmin() || $user->activeLocations()->count() > 1);

    // Build options for the quick switcher dropdown
    if ($user?->isSuperAdmin()) {
        $shops = Location::where('is_active', true)->orderBy('name')->get();
    } else {
        $shops = $user?->activeLocations()->orderBy('name')->get() ?? collect();
    }
@endphp

<div
    x-data="{ open: false }"
    class="flex items-center"
    style="margin-right: 0.5rem;"
>
    {{-- Shop pill --}}
    <button
        @if($canSwitch) @click="open = !open" @endif
        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition
            {{ $activeShop
                ? 'bg-primary-600 text-white hover:bg-primary-700'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200' }}"
        title="{{ $canSwitch ? 'Click to switch shop' : 'Your assigned shop' }}"
    >
        <x-heroicon-s-building-storefront class="h-3.5 w-3.5"/>
        <span>{{ $activeShop?->name ?? 'All Shops' }}</span>
        @if($canSwitch)
            <x-heroicon-s-chevron-down class="h-3 w-3 opacity-70"/>
        @endif
    </button>

    {{-- Dropdown --}}
    @if($canSwitch)
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute z-50 mt-1 w-56 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        style="top: 3.5rem; left: 1rem;"
    >
        <div class="p-1">
            <p class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
                Switch Shop
            </p>

            {{-- All shops option for super admin --}}
            @if($user?->isSuperAdmin())
            <a
                href="{{ route('filament.admin.pages.dashboard') }}"
                wire:navigate
                x-on:click="
                    fetch('/admin/switch-shop/0', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' } })
                        .then(() => window.location.href = '/admin')
                "
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700
                    {{ ! $activeShop ? 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300' : '' }}"
            >
                <x-heroicon-o-globe-alt class="h-4 w-4 text-gray-400"/>
                All Shops
                @if(! $activeShop)
                    <x-heroicon-s-check class="ml-auto h-3.5 w-3.5 text-primary-600"/>
                @endif
            </a>
            @endif

            {{-- Individual shops --}}
            @foreach($shops as $shop)
            <a
                href="#"
                x-on:click.prevent="
                    fetch('/admin/switch-shop/{{ $shop->id }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                    }).then(() => window.location.href = '/admin')
                "
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700
                    {{ $activeShop?->id === $shop->id ? 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300' : '' }}"
            >
                <x-heroicon-o-building-storefront class="h-4 w-4 text-gray-400"/>
                <span class="flex-1">{{ $shop->name }}</span>
                <span class="text-xs text-gray-400">{{ ucfirst($shop->type) }}</span>
                @if($activeShop?->id === $shop->id)
                    <x-heroicon-s-check class="h-3.5 w-3.5 text-primary-600"/>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
