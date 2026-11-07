<header class="fixed top-0 left-0 right-0 h-16 z-30 bg-[var(--color-surface-card)] border-b border-[var(--color-surface-border)] shadow-xs flex items-center justify-between px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <!-- Mobile Drawer Toggle Button -->
        <button 
            type="button" 
            @click="sidebarOpen = !sidebarOpen" 
            class="md:hidden p-2 rounded-md text-[var(--color-text-main)] hover:bg-[var(--color-surface)] focus:outline-none"
            aria-label="Buka menu navigasi"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Brand Name & Badge -->
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg bg-[var(--color-primary)] text-white flex items-center justify-center font-bold text-lg shadow-xs">
                A
            </div>
            <div>
                <span class="font-bold text-base tracking-tight text-[var(--color-primary)] block leading-tight">
                    ARSIP BPN
                </span>
                <span class="text-[10px] text-[var(--color-text-muted)] font-medium hidden sm:block leading-none">
                    Kantah Kabupaten Sumbawa
                </span>
            </div>
        </div>
    </div>

    <!-- Right Menu: Theme Picker & User Menu -->
    <div class="flex items-center gap-3">
        <!-- Theme Selector (Alpine Reactive) -->
        <div x-data="{ open: false }" class="relative">
            <button 
                @click="open = !open" 
                @click.outside="open = false"
                class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] hover:bg-[var(--color-surface)] transition text-[var(--color-text-main)]"
                title="Pilih Tema Tampilan"
            >
                <svg class="w-4 h-4 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4h4a4 4 0 014 4 4 4 0 01-4 4H7zM7 13a4 4 0 01-4-4 4 4 0 014-4h4a4 4 0 014 4 4 4 0 01-4 4H7zM17 13a4 4 0 01-4-4 4 4 0 014-4h4a4 4 0 014 4 4 4 0 01-4 4h-4z"/>
                </svg>
                <span class="capitalize hidden sm:inline" x-text="$store.theme.current">Tema</span>
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div 
                x-show="open" 
                x-transition 
                class="absolute right-0 mt-2 w-36 rounded-xl bg-[var(--color-surface-card)] border border-[var(--color-surface-border)] shadow-lg py-1.5 z-50"
                style="display: none;"
            >
                <div class="px-3 py-1 text-[11px] font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
                    Tema Warna
                </div>
                <button 
                    type="button"
                    @click="$store.theme.set('samudra'); open = false" 
                    class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--color-surface)] flex items-center justify-between"
                    :class="$store.theme.current === 'samudra' ? 'font-bold text-[var(--color-primary)]' : 'text-[var(--color-text-main)]'"
                >
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#003049] border border-white inline-block"></span>
                        <span>Samudra</span>
                    </div>
                    <span x-show="$store.theme.current === 'samudra'" class="text-[var(--color-primary)]">✓</span>
                </button>
                <button 
                    type="button"
                    @click="$store.theme.set('marun'); open = false" 
                    class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--color-surface)] flex items-center justify-between"
                    :class="$store.theme.current === 'marun' ? 'font-bold text-[var(--color-primary)]' : 'text-[var(--color-text-main)]'"
                >
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#780000] border border-white inline-block"></span>
                        <span>Marun</span>
                    </div>
                    <span x-show="$store.theme.current === 'marun'" class="text-[var(--color-primary)]">✓</span>
                </button>
                <button 
                    type="button"
                    @click="$store.theme.set('senja'); open = false" 
                    class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--color-surface)] flex items-center justify-between"
                    :class="$store.theme.current === 'senja' ? 'font-bold text-[var(--color-primary)]' : 'text-[var(--color-text-main)]'"
                >
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#003049] border border-[#c1121f] inline-block"></span>
                        <span>Senja</span>
                    </div>
                    <span x-show="$store.theme.current === 'senja'" class="text-[var(--color-primary)]">✓</span>
                </button>
            </div>
        </div>

        <!-- User Info / Profile Menu -->
        @auth
            <div x-data="{ userMenu: false }" class="relative">
                <button 
                    @click="userMenu = !userMenu" 
                    @click.outside="userMenu = false"
                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-[var(--color-surface)] transition"
                >
                    <div class="w-8 h-8 rounded-full bg-[var(--color-accent)] text-[var(--color-accent-contrast)] flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <div class="text-left hidden md:block">
                        <span class="text-xs font-semibold text-[var(--color-text-main)] block leading-tight">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[10px] text-[var(--color-text-muted)] font-medium leading-tight">
                            {{ auth()->user()->role?->label() }}
                        </span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- User Dropdown Menu -->
                <div 
                    x-show="userMenu" 
                    x-transition 
                    class="absolute right-0 mt-2 w-48 rounded-xl bg-[var(--color-surface-card)] border border-[var(--color-surface-border)] shadow-lg py-1.5 z-50"
                    style="display: none;"
                >
                    <div class="px-4 py-2 border-b border-[var(--color-surface-border)]">
                        <p class="text-xs font-semibold text-[var(--color-text-main)]">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-[var(--color-text-muted)] truncate">{{ auth()->user()->username }}</p>
                    </div>
                    <a href="/profile" class="w-full text-left px-4 py-2 text-xs text-[var(--color-text-main)] hover:bg-[var(--color-surface)] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Pengaturan Profil</span>
                    </a>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-xs text-[var(--color-danger)] hover:bg-[var(--color-surface)] flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Keluar (Logout)</span>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="/login" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90">
                Masuk
            </a>
        @endauth
    </div>
</header>
