<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme ?? 'samudra' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Sistem Informasi Arsip BPN Sumbawa') }}</title>

    <script>
        window.APP_USER_AUTHENTICATED = {{ auth()->check() ? 'true' : 'false' }};
        // Server theme is the single source of truth for authenticated users;
        // overwrite any conflicting localStorage value immediately so guest/stale theme does not persist.
        @auth
            try {
                localStorage.setItem('theme', '{{ auth()->user()->theme ?? 'samudra' }}');
            } catch (e) {}
        @endauth
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-surface)] text-[var(--color-text-main)] font-sans antialiased" x-data="{ sidebarOpen: false }">
    <!-- Topbar (Fixed 64px) -->
    <x-topbar />

    <!-- Sidebar Desktop (Fixed 256px) & Mobile Drawer -->
    <x-sidebar />

    <!-- Main Content Area -->
    <div class="md:pl-64 pt-16 min-h-screen flex flex-col transition-all duration-200">
        <!-- Toast / Flash Notifications -->
        <x-toast />

        <!-- Page Header -->
        @if(isset($header))
            <div class="bg-[var(--color-surface-card)] border-b border-[var(--color-surface-border)] px-4 py-4 sm:px-6 lg:px-8 shadow-xs">
                <div class="max-w-7xl mx-auto">
                    {{ $header }}
                </div>
            </div>
        @endif

        <!-- Main Content (12-column grid container) -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-12 gap-6">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-[var(--color-surface-card)] border-t border-[var(--color-surface-border)] py-4 px-6 text-center text-xs text-[var(--color-text-muted)]">
            &copy; {{ date('Y') }} Kantor Pertanahan Kabupaten Sumbawa. Sistem Informasi Arsip.
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
