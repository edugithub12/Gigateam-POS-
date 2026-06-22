<div class="space-y-3">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Available quantity for <span class="font-semibold">{{ $product->name }}</span> ({{ $product->sku }}) across all locations.
    </p>

    <div class="divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        @forelse($stocks as $stock)
            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800">
                <div>
                    <p class="font-medium text-gray-800 dark:text-white">{{ $stock->location->name }}</p>
                    <p class="text-xs text-gray-400">{{ ucfirst($stock->location->type) }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-lg font-bold {{ $stock->quantity <= 0 ? 'text-red-500' : ($stock->isLowStock() ? 'text-yellow-500' : 'text-green-600') }}">
                        {{ $stock->quantity }}
                    </span>

                    @if($stock->location_id !== $userLocationId && $stock->quantity > 0)
                        <a href="{{ route('filament.admin.resources.stock-transfers.create', ['product_id' => $product->id, 'from_location_id' => $stock->location_id]) }}"
                           class="text-xs font-medium px-3 py-1.5 rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">
                            Request
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-4 py-6 text-center text-gray-400">
                No stock records found for this product at any location.
            </div>
        @endforelse
    </div>
</div>