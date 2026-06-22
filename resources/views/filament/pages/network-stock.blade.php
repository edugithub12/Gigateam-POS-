<x-filament-panels::page>
<div>
    <div class="space-y-4">

        <div class="rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-800 dark:bg-primary-950">
            <p class="text-sm text-primary-700 dark:text-primary-300">
                Every shop runs its own independent catalog. Browse what each shop carries below, and use
                <strong>Request Transfer</strong> to ask a shop to send you stock of something they have.
            </p>
        </div>

        <x-filament::section>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search product name or SKU across all shops..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                </div>
                <div class="sm:w-56">
                    <select
                        wire:model.live="filterShopId"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">All shops</option>
                        @foreach($this->getShops() as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        @php $catalog = $this->getCatalogByShop(); @endphp

        @forelse($catalog as $shopData)
        <x-filament::section>
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-building-storefront class="h-5 w-5 text-gray-400"/>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $shopData['shop_name'] }}
                    </h3>
                    @if($shopData['is_current_shop'])
                    <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                        Your shop
                    </span>
                    @endif
                    <span class="text-xs text-gray-400">{{ ucfirst($shopData['shop_type']) }}</span>
                </div>
                <span class="text-sm text-gray-400">{{ $shopData['product_count'] }} products</span>
            </div>

            @if(empty($shopData['products']))
                <p class="py-6 text-center text-sm text-gray-400">No products in this shop's catalog yet.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                            <th class="pb-2 pr-4 font-medium text-gray-600 dark:text-gray-400">Product</th>
                            <th class="pb-2 pr-4 font-medium text-gray-600 dark:text-gray-400">SKU</th>
                            <th class="pb-2 pr-4 font-medium text-gray-600 dark:text-gray-400">Category</th>
                            <th class="pb-2 pr-4 text-center font-medium text-gray-600 dark:text-gray-400">Stock</th>
                            <th class="pb-2 pr-4 text-right font-medium text-gray-600 dark:text-gray-400">Price</th>
                            @if(! $shopData['is_current_shop'])
                            <th class="pb-2 text-center font-medium text-gray-600 dark:text-gray-400">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($shopData['products'] as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</td>
                            <td class="py-2 pr-4 font-mono text-xs text-gray-500">{{ $product['sku'] }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $product['category'] }}</td>
                            <td class="py-2 pr-4 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                    {{ $product['status'] === 'out' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                    : ($product['status'] === 'low' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300') }}">
                                    {{ $product['quantity'] }} {{ $product['unit'] }}
                                </span>
                            </td>
                            <td class="py-2 text-right text-gray-700 dark:text-gray-300">
                                KES {{ number_format($product['price'], 2) }}
                            </td>
                            @if(! $shopData['is_current_shop'])
                            <td class="py-2 text-center">
                                @if($product['quantity'] > 0)
                                <button
                                    wire:click="requestTransferAction({{ $product['id'] }})"
                                    class="text-xs text-primary-600 underline hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200"
                                >
                                    Request
                                </button>
                                @else
                                <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </x-filament::section>
        @empty
        <x-filament::section>
            <div class="py-12 text-center text-gray-400">
                <x-heroicon-o-building-storefront class="mx-auto h-10 w-10 mb-2"/>
                <p>No shops found.</p>
            </div>
        </x-filament::section>
        @endforelse

    </div>
</div>
</x-filament-panels::page>
