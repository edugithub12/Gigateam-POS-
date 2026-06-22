<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gigateam POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        @media print {
            /* Hide everything on the page */
            body * {
                visibility: hidden !important;
            }

            /* Show only the receipt */
            #receipt-print,
            #receipt-print * {
                visibility: visible !important;
            }

            /* Position receipt at top-left of the printed page */
            #receipt-print {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                z-index: 99999 !important;
                background: #fff !important;
            }

            /* Hide the Print / PDF / New Sale buttons */
            .no-print {
                display: none !important;
            }

            /* Remove browser default margins on print */
            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body class="h-full bg-gray-950 text-gray-100 antialiased">

    {{-- Top navigation bar --}}
    <nav class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-4 shrink-0 z-50 no-print">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/gigateam-logo.png') }}" alt="Gigateam Solutions" class="h-10 w-auto">
            <div class="flex flex-col justify-center leading-tight">
                <span style="
                    font-family: 'Arial Black', 'Arial', sans-serif;
                    font-size: 1rem;
                    font-weight: 900;
                    color: #111111;
                    letter-spacing: 0.04em;
                    line-height: 1.2;
                    text-transform: uppercase;
                ">Gigateam Solutions Ltd</span>
                <span style="
                    font-family: 'Georgia', serif;
                    font-size: 0.72rem;
                    font-weight: 600;
                    font-style: italic;
                    color: #DC2626;
                    letter-spacing: 0.02em;
                    line-height: 1.2;
                ">Secured &amp; Connected</span>
            </div>
        </div>

        {{-- RIGHT: Clock, User, Links --}}
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span id="pos-clock" class="font-mono text-gray-700 font-semibold"></span>
            <span class="text-gray-300">|</span>
            <span class="text-gray-700">{{ auth()->user()->name }}</span>
            <a href="/admin" class="text-red-600 hover:text-red-500 transition text-xs font-medium">Admin Panel →</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-gray-400 hover:text-red-500 transition text-xs">Logout</button>
            </form>
        </div>
    </nav>

    {{-- Toast notifications --}}
    <div
        x-data="{ toasts: [] }"
        x-on:notify.window="
            const id = Date.now();
            toasts.push({ id, type: $event.detail.type ?? 'info', message: $event.detail.message ?? '' });
            setTimeout(() => { toasts = toasts.filter(t => t.id !== id) }, 4000);
        "
        class="fixed top-16 right-4 z-[99999] flex flex-col gap-2 pointer-events-none no-print"
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
    <main class="h-[calc(100vh-3.5rem)]">
        {{ $slot }}
    </main>

    @livewireScripts

    <script>
        function tick() {
            const el = document.getElementById('pos-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-KE', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
        tick();
        setInterval(tick, 1000);
    </script>

    {{-- Service Worker + offline detection --}}
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/pos-sw.js', { scope: '/' })
                .then(reg => console.log('SW registered:', reg.scope))
                .catch(err => console.log('SW registration failed:', err));
        }

        // Auto-redirect to offline POS when connection drops
        window.addEventListener('offline', () => {
            setTimeout(() => {
                if (!navigator.onLine) {
                    window.location.href = '/pos-offline.html';
                }
            }, 1500);
        });
    </script>
</body>
</html>