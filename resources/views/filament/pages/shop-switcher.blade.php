<x-filament-panels::page>
<div>
    <div class="space-y-4">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <x-heroicon-o-building-storefront class="h-8 w-8 text-primary-500"/>
                <div>
                    <p class="text-sm text-gray-500">Currently viewing</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ activeShop()?->name ?? '— All Shops —' }}
                    </p>
                    @if(activeShop())
                    <p class="text-sm text-gray-400">
                        {{ ucfirst(activeShop()->type) }}
                        @if(activeShop()->address) · {{ activeShop()->address }} @endif
                    </p>
                    @endif
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Available Shops">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->getShopOptions() as $id => $name)
                <div class="rounded-xl border p-4 transition {{ activeShopId() === $id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $name }}</span>
                        @if(activeShopId() === $id)
                            <x-heroicon-s-check-circle class="h-5 w-5 text-primary-500"/>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <p class="mt-4 text-sm text-gray-400">
                Use the shop switcher in the top bar to change your active shop.
            </p>
        </x-filament::section>
    </div>
</div>
</x-filament-panels::page>
