<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
                    Dashboard Ringkasan
                </h1>
                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                    Selamat datang di Sistem Informasi Arsip Kantah ATR/BPN Kabupaten Sumbawa
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-[var(--color-text-muted)] font-medium">Tema Aktif:</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase bg-[var(--color-accent)] text-[var(--color-accent-contrast)]" x-text="$store.theme.current"></span>
            </div>
        </div>
    </x-slot>

    <!-- 3 Fixed Span Stat Cards (col-span-12 sm:col-span-6 md:col-span-4) -->
    <x-stat-card 
        title="Menunggu Persetujuan" 
        :value="$pendingCount ?? 0"
        variant="warning"
        description="Permohonan booking berkas warkah"
        href="/loans"
    >
        <x-slot name="icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card 
        title="Sedang Dipinjam" 
        :value="$borrowedCount ?? 0"
        variant="danger"
        description="Berkas fisik di luar rak penyimpanan"
        href="/loans"
    >
        <x-slot name="icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card 
        title="Total Berkas Terdaftar" 
        :value="$totalDocuments ?? 0"
        variant="primary"
        description="Buku Tanah, Surat Ukur, & Warkah"
        href="/documents"
    >
        <x-slot name="icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <!-- Theme Switcher & Preview Panel (col-span-12) -->
    <div class="col-span-12">
        <x-card title="Pengujian Fondasi Desain & Tiga Tema (A7)" subtitle="Verifikasi penerapan palet warna semantik dan pemilih tema instan tanpa reload">
            <div class="space-y-4">
                <p class="text-xs text-[var(--color-text-muted)] leading-relaxed">
                    Sistem ini mendukung 3 preset tema baku dengan design token CSS variables. Klik tombol di bawah ini untuk menguji perubahan tema seketika:
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <!-- Samudra Card -->
                    <div 
                        @click="$store.theme.set('samudra')"
                        class="p-4 rounded-xl border-2 cursor-pointer transition hover:scale-[1.02]"
                        :class="$store.theme.current === 'samudra' ? 'border-[#003049] ring-2 ring-[#003049]/20 shadow-md' : 'border-[var(--color-surface-border)]'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-[#003049]">Tema Samudra</span>
                            <span x-show="$store.theme.current === 'samudra'" class="text-xs font-bold text-[#003049]">Aktif</span>
                        </div>
                        <div class="flex gap-1.5 mb-3">
                            <div class="w-6 h-6 rounded-full bg-[#003049] border" title="Sidebar & Primary #003049"></div>
                            <div class="w-6 h-6 rounded-full bg-[#669bbc] border" title="Accent #669bbc"></div>
                            <div class="w-6 h-6 rounded-full bg-[#fdf0d5] border" title="Surface #fdf0d5"></div>
                            <div class="w-6 h-6 rounded-full bg-[#c1121f] border" title="Danger #c1121f"></div>
                        </div>
                        <p class="text-[11px] text-slate-500">Sidebar #003049, aksen #669bbc, latar #fdf0d5, bahaya #c1121f. (Default sistem)</p>
                    </div>

                    <!-- Marun Card -->
                    <div 
                        @click="$store.theme.set('marun')"
                        class="p-4 rounded-xl border-2 cursor-pointer transition hover:scale-[1.02]"
                        :class="$store.theme.current === 'marun' ? 'border-[#780000] ring-2 ring-[#780000]/20 shadow-md' : 'border-[var(--color-surface-border)]'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-[#780000]">Tema Marun</span>
                            <span x-show="$store.theme.current === 'marun'" class="text-xs font-bold text-[#780000]">Aktif</span>
                        </div>
                        <div class="flex gap-1.5 mb-3">
                            <div class="w-6 h-6 rounded-full bg-[#780000] border" title="Sidebar & Primary #780000"></div>
                            <div class="w-6 h-6 rounded-full bg-[#c1121f] border" title="Accent & Danger #c1121f"></div>
                            <div class="w-6 h-6 rounded-full bg-[#fdf0d5] border" title="Surface #fdf0d5"></div>
                        </div>
                        <p class="text-[11px] text-slate-500">Sidebar #780000, aksen #c1121f, latar #fdf0d5.</p>
                    </div>

                    <!-- Senja Card -->
                    <div 
                        @click="$store.theme.set('senja')"
                        class="p-4 rounded-xl border-2 cursor-pointer transition hover:scale-[1.02]"
                        :class="$store.theme.current === 'senja' ? 'border-[#003049] ring-2 ring-[#c1121f]/20 shadow-md' : 'border-[var(--color-surface-border)]'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-[#003049]">Tema Senja</span>
                            <span x-show="$store.theme.current === 'senja'" class="text-xs font-bold text-[#c1121f]">Aktif</span>
                        </div>
                        <div class="flex gap-1.5 mb-3">
                            <div class="w-6 h-6 rounded-full bg-[#003049] border" title="Sidebar & Primary #003049"></div>
                            <div class="w-6 h-6 rounded-full bg-[#c1121f] border" title="Accent & Danger #c1121f"></div>
                            <div class="w-6 h-6 rounded-full bg-[#fdf0d5] border" title="Surface #fdf0d5"></div>
                        </div>
                        <p class="text-[11px] text-slate-500">Sidebar #003049, aksen #c1121f, latar #fdf0d5.</p>
                    </div>
                </div>

                <!-- Contrast Compliance Badge Preview -->
                <div class="pt-4 border-t border-[var(--color-surface-border)] flex flex-wrap items-center gap-3">
                    <span class="text-xs font-semibold text-[var(--color-text-main)]">Pratinjau Status Badge:</span>
                    <x-badge-status status="available" />
                    <x-badge-status status="booked" />
                    <x-badge-status status="borrowed" />
                    <x-badge-status status="lost" />
                    <x-badge-status status="invalid" />
                </div>
            </div>
        </x-card>
    </div>

    <!-- Data Master Count Summary Card (col-span-12) -->
    <div class="col-span-12">
        <x-card title="Status Data Master Kantor Pertanahan Sumbawa (Hasil Seeder)" subtitle="Master klasifikasi dan wilayah se-Kabupaten Sumbawa">
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3 text-center">
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Kecamatan</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\District::count() }}</p>
                </div>
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Desa/Kelurahan</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\Village::count() }}</p>
                </div>
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Seksi</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\Section::count() }}</p>
                </div>
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Rak Arsip</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\StorageRack::count() }}</p>
                </div>
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Jenis Berkas</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\DocumentType::count() }}</p>
                </div>
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Jenis Hak</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\LandRightType::count() }}</p>
                </div>
                <div class="p-3 rounded-lg bg-[var(--color-surface)] border border-[var(--color-surface-border)]">
                    <p class="text-xs text-[var(--color-text-muted)] font-semibold">Kegiatan</p>
                    <p class="text-xl font-black mt-1 text-[var(--color-primary)]">{{ \App\Models\ServiceActivity::count() }}</p>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
