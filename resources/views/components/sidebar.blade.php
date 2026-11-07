<div>
    <!-- Mobile Backdrop -->
    <div 
        x-show="sidebarOpen" 
        @click="sidebarOpen = false" 
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 z-40 md:hidden"
        style="display: none;"
    ></div>

    <!-- Sidebar Container -->
    <aside 
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed top-16 bottom-0 left-0 w-64 z-40 bg-[var(--color-sidebar)] text-[var(--color-sidebar-text)] transition-transform duration-300 ease-in-out overflow-y-auto flex flex-col justify-between shadow-lg"
    >
        <div class="py-4 px-3 space-y-6">
            <!-- Navigation Links -->
            <nav class="space-y-1">
                <!-- Dashboard -->
                <a 
                    href="/dashboard" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->is('dashboard*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] shadow-xs font-bold' : 'text-slate-200 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                >
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- Katalog Berkas -->
                <a 
                    href="/documents" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->is('documents*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] shadow-xs font-bold' : 'text-slate-200 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                >
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                    </svg>
                    <span>Katalog Berkas</span>
                </a>

                <!-- Peminjaman & Pengembalian -->
                <a 
                    href="/loans" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->is('loans*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] shadow-xs font-bold' : 'text-slate-200 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                >
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Peminjaman Berkas</span>
                </a>

                @if(auth()->check() && auth()->user()->canManageArchives())
                    <!-- Riwayat / Jejak Audit -->
                    <a 
                        href="/audit" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->is('audit*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] shadow-xs font-bold' : 'text-slate-200 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Jejak Audit</span>
                    </a>
                @endif
            </nav>

            @if(auth()->check() && auth()->user()->canManageArchives())
                <!-- Master Data Section -->
                <div class="space-y-1">
                    <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        Master Data Arsip
                    </p>
                    <a 
                        href="/master/storage-racks" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition {{ request()->is('master/storage-racks*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] font-bold' : 'text-slate-300 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <span>• Rak Penyimpanan</span>
                    </a>
                    <a 
                        href="/master/document-types" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition {{ request()->is('master/document-types*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] font-bold' : 'text-slate-300 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <span>• Jenis Dokumen</span>
                    </a>
                    <a 
                        href="/master/land-right-types" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition {{ request()->is('master/land-right-types*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] font-bold' : 'text-slate-300 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <span>• Jenis Hak Tanah</span>
                    </a>
                    <a 
                        href="/master/service-activities" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition {{ request()->is('master/service-activities*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] font-bold' : 'text-slate-300 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <span>• Kegiatan Pelayanan</span>
                    </a>
                </div>
            @endif

            @if(auth()->check() && auth()->user()->isSuperAdmin())
                <!-- Super Admin Only: System & Users -->
                <div class="space-y-1">
                    <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        Administrasi Sistem
                    </p>
                    <a 
                        href="/master/sections" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition {{ request()->is('master/sections*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] font-bold' : 'text-slate-300 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <span>• Seksi Organisasi</span>
                    </a>
                    <a 
                        href="/users" 
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition {{ request()->is('users*') ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-active-text)] font-bold' : 'text-slate-300 hover:bg-[var(--color-sidebar-hover)] font-medium' }}"
                    >
                        <span>• Manajemen Pengguna</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Sidebar Footer Status -->
        <div class="p-3 border-t border-white/10 text-[11px] text-slate-400">
            <div class="flex items-center justify-between">
                <span>Versi Laravel 13</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/20 text-emerald-300">
                    Aktif
                </span>
            </div>
        </div>
    </aside>
</div>
