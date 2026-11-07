<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="samudra">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Sistem Informasi Arsip BPN Sumbawa') }}</title>

    <script>
        window.APP_USER_AUTHENTICATED = false;
        // For guest pages (such as login), load preview theme from localStorage
        (function() {
            try {
                var saved = localStorage.getItem('theme');
                if (saved && ['samudra', 'marun', 'senja'].includes(saved)) {
                    document.documentElement.setAttribute('data-theme', saved);
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-surface)] text-[var(--color-text-main)] font-sans antialiased flex flex-col justify-center items-center p-4">
    <x-toast />
    <div class="w-full max-w-md">
        {{ $slot }}
    </div>
</body>
</html>
