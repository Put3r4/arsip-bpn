<x-app-layout title="Pengaturan Profil">
    <x-slot name="header">
        <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
            Pengaturan Akun & Profil
        </h1>
        <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
            Kelola kata sandi dan preferensi tema tampilan sistem Anda
        </p>
    </x-slot>

    <!-- Kolom Kiri: Informasi Akun & Pemilih Tema (col-span-12 md:col-span-5) -->
    <div class="col-span-12 md:col-span-5 space-y-6">
        <!-- Info Akun -->
        <x-card title="Informasi Pengguna">
            <div class="space-y-3 text-xs">
                <div class="flex items-center gap-3 pb-3 border-b border-[var(--color-surface-border)]">
                    <div class="w-12 h-12 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center font-bold text-base shadow-xs">
                        {{ substr($user->name, 0, 2) }}
                    </div>
                    <div>
                        <p class="font-bold text-sm text-[var(--color-text-main)]">{{ $user->name }}</p>
                        <p class="text-[var(--color-text-muted)]">{{ '@' . $user->username }}</p>
                    </div>
                </div>

                <div class="flex justify-between py-1 border-b border-[var(--color-surface-border)]">
                    <span class="text-[var(--color-text-muted)]">Peran Sistem:</span>
                    <span class="font-semibold text-[var(--color-primary)]">{{ $user->role->label() }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-[var(--color-surface-border)]">
                    <span class="text-[var(--color-text-muted)]">NIP:</span>
                    <span class="font-semibold text-[var(--color-text-main)]">{{ $user->nip ?? '-' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-[var(--color-surface-border)]">
                    <span class="text-[var(--color-text-muted)]">Seksi Organisasi:</span>
                    <span class="font-semibold text-[var(--color-text-main)]">{{ $user->section?->name ?? '-' }}</span>
                </div>

                <div class="flex justify-between py-1">
                    <span class="text-[var(--color-text-muted)]">Status Akun:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                        Aktif
                    </span>
                </div>
            </div>
        </x-card>

        <!-- Pemilih Tema Tampilan -->
        <x-card title="Preferensi Tema Tampilan (A7)" subtitle="Perubahan tema langsung disimpan ke profil pengguna">
            <div class="space-y-3">
                <p class="text-xs text-[var(--color-text-muted)]">
                    Pilih preset palet warna sesuai preferensi kenyamanan Anda. Tema disimpan ke database dan aktif seketika tanpa kedipan.
                </p>

                <div class="space-y-2">
                    <button 
                        type="button" 
                        @click="$store.theme.set('samudra')"
                        class="w-full text-left p-3 rounded-xl border flex items-center justify-between transition hover:bg-[var(--color-surface)]"
                        :class="$store.theme.current === 'samudra' ? 'border-[#003049] bg-[var(--color-surface)] shadow-xs' : 'border-[var(--color-surface-border)]'"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 rounded-full bg-[#003049] border border-white shrink-0"></span>
                            <div>
                                <p class="text-xs font-bold text-[#003049]">Tema Samudra</p>
                                <p class="text-[10px] text-slate-500">Palet Biru Klasik BPN Sumbawa (Default)</p>
                            </div>
                        </div>
                        <span x-show="$store.theme.current === 'samudra'" class="text-xs font-bold text-[#003049]">✓ Aktif</span>
                    </button>

                    <button 
                        type="button" 
                        @click="$store.theme.set('marun')"
                        class="w-full text-left p-3 rounded-xl border flex items-center justify-between transition hover:bg-[var(--color-surface)]"
                        :class="$store.theme.current === 'marun' ? 'border-[#780000] bg-[var(--color-surface)] shadow-xs' : 'border-[var(--color-surface-border)]'"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 rounded-full bg-[#780000] border border-white shrink-0"></span>
                            <div>
                                <p class="text-xs font-bold text-[#780000]">Tema Marun</p>
                                <p class="text-[10px] text-slate-500">Sidebar Marun Gelap & Aksen Merah Bata</p>
                            </div>
                        </div>
                        <span x-show="$store.theme.current === 'marun'" class="text-xs font-bold text-[#780000]">✓ Aktif</span>
                    </button>

                    <button 
                        type="button" 
                        @click="$store.theme.set('senja')"
                        class="w-full text-left p-3 rounded-xl border flex items-center justify-between transition hover:bg-[var(--color-surface)]"
                        :class="$store.theme.current === 'senja' ? 'border-[#003049] bg-[var(--color-surface)] shadow-xs' : 'border-[var(--color-surface-border)]'"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 rounded-full bg-[#003049] border border-[#c1121f] shrink-0"></span>
                            <div>
                                <p class="text-xs font-bold text-[#003049]">Tema Senja</p>
                                <p class="text-[10px] text-slate-500">Sidebar Biru Malam & Aksen Merah Senja</p>
                            </div>
                        </div>
                        <span x-show="$store.theme.current === 'senja'" class="text-xs font-bold text-[#003049]">✓ Aktif</span>
                    </button>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Kolom Kanan: Form Ganti Kata Sandi (col-span-12 md:col-span-7) -->
    <div class="col-span-12 md:col-span-7">
        <x-card title="Ubah Kata Sandi" subtitle="Perbarui kata sandi akun Anda secara berkala demi keamanan">
            <form method="POST" action="/profile/password" class="space-y-4">
                @csrf
                @method('PATCH')

                <x-input 
                    label="Kata Sandi Saat Ini" 
                    name="current_password" 
                    type="password" 
                    placeholder="Masukkan kata sandi lama Anda" 
                    required 
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input 
                        label="Kata Sandi Baru" 
                        name="password" 
                        type="password" 
                        placeholder="Minimal 8 karakter" 
                        required 
                    />

                    <x-input 
                        label="Konfirmasi Kata Sandi Baru" 
                        name="password_confirmation" 
                        type="password" 
                        placeholder="Ulangi kata sandi baru" 
                        required 
                    />
                </div>

                <div class="pt-2 flex justify-end">
                    <x-button type="submit" variant="primary" size="md">
                        Simpan Kata Sandi Baru
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
