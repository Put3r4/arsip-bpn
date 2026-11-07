<x-guest-layout title="Masuk">
    <div class="bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-8 space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 mx-auto rounded-xl bg-[var(--color-primary)] text-white flex items-center justify-center font-black text-2xl shadow-sm">
                A
            </div>
            <h1 class="text-xl font-black tracking-tight text-[var(--color-primary)]">
                Sistem Informasi Arsip
            </h1>
            <p class="text-xs text-[var(--color-text-muted)]">
                Kantor Pertanahan ATR/BPN Kabupaten Sumbawa
            </p>
        </div>

        <!-- Form -->
        <form method="POST" action="/login" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="p-3 rounded-lg border border-rose-300 bg-rose-50 text-rose-900 text-xs flex items-start gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-input 
                label="Nama Pengguna (Username)" 
                name="username" 
                placeholder="Masukkan username" 
                required 
                autofocus 
            />

            <x-input 
                label="Kata Sandi" 
                name="password" 
                type="password" 
                placeholder="••••••••" 
                required 
            />

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-[var(--color-text-muted)]">
                    <input type="checkbox" name="remember" class="rounded border-[var(--color-surface-border)] text-[var(--color-primary)] focus:ring-[var(--color-accent)]">
                    <span>Ingat saya</span>
                </label>
            </div>

            <x-button type="submit" variant="primary" size="md" class="w-full">
                Masuk ke Aplikasi
            </x-button>
        </form>

        <!-- Theme Preview Info -->
        <div class="pt-4 border-t border-[var(--color-surface-border)] text-center text-xs text-[var(--color-text-muted)] space-y-2">
            <p>Pilih Tema Pratinjau:</p>
            <div class="flex items-center justify-center gap-2">
                <button 
                    type="button" 
                    @click="$store.theme.set('samudra')"
                    class="px-2.5 py-1 rounded text-[11px] font-semibold border border-[#003049] text-[#003049] hover:bg-[#003049] hover:text-white transition"
                >
                    Samudra
                </button>
                <button 
                    type="button" 
                    @click="$store.theme.set('marun')"
                    class="px-2.5 py-1 rounded text-[11px] font-semibold border border-[#780000] text-[#780000] hover:bg-[#780000] hover:text-white transition"
                >
                    Marun
                </button>
                <button 
                    type="button" 
                    @click="$store.theme.set('senja')"
                    class="px-2.5 py-1 rounded text-[11px] font-semibold border border-[#c1121f] text-[#c1121f] hover:bg-[#c1121f] hover:text-white transition"
                >
                    Senja
                </button>
            </div>
        </div>
    </div>
</x-guest-layout>
