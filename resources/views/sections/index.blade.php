<x-app-layout title="Master Seksi Organisasi">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
                    Master Seksi Organisasi
                </h1>
                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                    Kelola data seksi / unit kerja internal Kantor Pertanahan ATR/BPN Kabupaten Sumbawa
                </p>
            </div>
            <button 
                type="button" 
                @click="$dispatch('open-create-section-modal')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90 transition shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Seksi</span>
            </button>
        </div>
    </x-slot>

    <!-- Main Content Grid -->
    <div 
        class="col-span-12"
        x-data="{
            createModalOpen: false,
            editModalOpen: false,
            deleteModalOpen: false,
            editForm: { id: null, name: '' },
            deleteTarget: { id: null, name: '' },
            
            openEdit(item) {
                this.editForm = { id: item.id, name: item.name };
                this.editModalOpen = true;
            },

            openDelete(item) {
                this.deleteTarget = { id: item.id, name: item.name };
                this.deleteModalOpen = true;
            }
        }"
        @open-create-section-modal.window="createModalOpen = true"
    >
        <x-x-data-table 
            endpoint="/master/sections/data"
            id="sectionsTable"
            defaultSort="name"
            defaultDirection="asc"
        >
            <x-slot name="header">
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('id')">
                    <div class="flex items-center gap-1">
                        <span>#</span>
                        <span x-show="sort === 'id'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('name')">
                    <div class="flex items-center gap-1">
                        <span>Nama Seksi</span>
                        <span x-show="sort === 'name'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer text-center" @click="sortBy('users_count')">
                    <div class="flex items-center justify-center gap-1">
                        <span>Jumlah Pegawai</span>
                        <span x-show="sort === 'users_count'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('created_at')">
                    <div class="flex items-center gap-1">
                        <span>Dibuat Pada</span>
                        <span x-show="sort === 'created_at'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </x-slot>

            <template x-for="item in items" :key="item.id">
                <tr class="hover:bg-[var(--color-surface)]/50 transition">
                    <td class="px-4 py-3 font-mono text-slate-500" x-text="item.id"></td>
                    <td class="px-4 py-3 font-medium text-[var(--color-text-main)]" x-text="item.name"></td>
                    <td class="px-4 py-3 text-center">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                            :class="item.users_count > 0 ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-600'"
                            x-text="item.users_count + ' Pegawai'"
                        ></span>
                    </td>
                    <td class="px-4 py-3 text-slate-500" x-text="item.created_at"></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <!-- Edit Button -->
                            <button 
                                type="button" 
                                @click="openEdit(item)"
                                class="p-1 rounded text-[var(--color-primary)] hover:bg-[var(--color-surface)] transition"
                                title="Ubah Seksi"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            <!-- Delete Button -->
                            <template x-if="item.can_delete">
                                <button 
                                    type="button" 
                                    @click="openDelete(item)"
                                    class="p-1 rounded text-[var(--color-danger)] hover:bg-rose-50 transition"
                                    title="Hapus Seksi"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </template>
                            <template x-if="!item.can_delete">
                                <span 
                                    class="p-1 text-slate-300 cursor-not-allowed" 
                                    title="Seksi tidak dapat dihapus karena masih digunakan oleh pegawai"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </span>
                            </template>
                        </div>
                    </td>
                </tr>
            </template>
        </x-x-data-table>

        <!-- Modal Tambah Seksi -->
        <div 
            x-show="createModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="createModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Tambah Seksi Organisasi Baru</h3>
                    <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form method="POST" action="/master/sections" class="space-y-4">
                    @csrf
                    <x-input 
                        label="Nama Seksi" 
                        name="name" 
                        placeholder="Contoh: Seksi Penetapan Hak dan Pendaftaran" 
                        required 
                    />

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button type="button" variant="secondary" size="sm" @click="createModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Simpan Seksi
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Seksi -->
        <div 
            x-show="editModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="editModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Ubah Seksi Organisasi</h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form :action="'/master/sections/' + editForm.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                            Nama Seksi
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            x-model="editForm.name" 
                            required 
                            class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button type="button" variant="secondary" size="sm" @click="editModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Simpan Perubahan
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Seksi -->
        <div 
            x-show="deleteModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="deleteModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center gap-3 text-[var(--color-danger)]">
                    <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[var(--color-text-main)]">Konfirmasi Hapus Seksi</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-xs text-[var(--color-text-main)] leading-relaxed">
                    Apakah Anda yakin ingin menghapus seksi <strong x-text="deleteTarget.name"></strong>?
                </p>

                <form :action="'/master/sections/' + deleteTarget.id" method="POST" class="flex justify-end gap-2 pt-2">
                    @csrf
                    @method('DELETE')
                    <x-button type="button" variant="secondary" size="sm" @click="deleteModalOpen = false">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="danger" size="sm">
                        Ya, Hapus Seksi
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
