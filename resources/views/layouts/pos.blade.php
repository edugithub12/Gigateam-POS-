<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gigateam POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-950 text-gray-100 antialiased">

    {{-- Top navigation bar --}}
    <nav class="h-12 bg-gray-900 border-b border-gray-800 flex items-center justify-between px-4 shrink-0 z-50">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/gigateam-logo.png') }}" alt="Gigateam" class="h-8 w-auto">
            <span class="font-semibold text-white text-sm tracking-wide">Gigateam Solutions</span>
            <span class="text-gray-600 text-xs mx-1">|</span>
            <span class="text-gray-400 text-xs">Point of Sale</span>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-400">
            <span id="pos-clock" class="font-mono text-gray-300"></span>
            <span class="text-gray-600">|</span>
            <span class="text-gray-300">{{ auth()->user()->name }}</span>
            <a href="/admin" class="text-red-400 hover:text-red-300 transition text-xs">Admin Panel →</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-gray-500 hover:text-red-400 transition text-xs">Logout</button>
            </form>
        </div>
    </nav>

    {{-- Toast notifications — x-data here, listens for Livewire dispatch('notify') --}}
    <div
        x-data="{ toasts: [] }"
        x-on:notify.window="
            const id = Date.now();
            toasts.push({ id, type: $event.detail.type ?? 'info', message: $event.detail.message ?? '' });
            setTimeout(() => { toasts = toasts.filter(t => t.id !== id) }, 4000);
        "
        class="fixed top-14 right-4 z-[99999] flex flex-col gap-2 pointer-events-none"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition duration-150"
                x-transition:leave-end="opacity-0"
                :class="{
                    'bg-green-900 border-green-700 text-green-200': toast.type === 'success',
                    'bg-red-900 border-red-700 text-red-200': toast.type === 'error',
                    'bg-yellow-900 border-yellow-700 text-yellow-200': toast.type === 'warning',
                    'bg-blue-900 border-blue-700 text-blue-200': toast.type === 'info',
                }"
                class="border rounded-lg px-4 py-2.5 text-sm max-w-xs shadow-xl pointer-events-auto"
                x-text="toast.message"
            ></div>
        </template>
    </div>

    {{-- Main content --}}
    <main class="h-[calc(100vh-3rem)]">
        {{ $slot }}
    </main>

    @livewireScripts

    <script>
        // Live clock
        function tick() {
            const el = document.getElementById('pos-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-KE', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
        tick();
        setInterval(tick, 1000);
    </script>
</body>
</html>