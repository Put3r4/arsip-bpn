<x-app-layout title="Manajemen Pengguna">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
                    Manajemen Pengguna
                </h1>
                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                    Kelola akun, hak akses role (Super Admin, Petugas Arsip, Pegawai), dan status pegawai
                </p>
            </div>
            <button 
                type="button" 
                @click="$dispatch('open-create-user-modal')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90 transition shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span>Tambah Pengguna</span>
            </button>
        </div>
    </x-slot>

    <!-- Main Content Container with Modals State -->
    <div 
        class="col-span-12"
        x-data="{
            createModalOpen: false,
            editModalOpen: false,
            resetModalOpen: false,
            deleteModalOpen: false,

            editForm: {
                id: null,
                name: '',
                username: '',
                nip: '',
                role: 'employee',
                section_id: '',
                is_active: true
            },
            resetForm: {
                id: null,
                name: '',
                username: ''
            },
            deleteTarget: {
                id: null,
                name: '',
                username: ''
            },

            openEdit(item) {
                this.editForm = {
                    id: item.id,
                    name: item.name,
                    username: item.username,
                    nip: item.nip === '-' ? '' : item.nip,
                    role: item.role,
                    section_id: item.section_id || '',
                    is_active: Boolean(item.is_active)
                };
                this.editModalOpen = true;
            },

            openReset(item) {
                this.resetForm = {
                    id: item.id,
                    name: item.name,
                    username: item.username
                };
                this.resetModalOpen = true;
            },

            openDelete(item) {
                this.deleteTarget = {
                    id: item.id,
                    name: item.name,
                    username: item.username
                };
                this.deleteModalOpen = true;
            }
        }"
        @open-create-user-modal.window="createModalOpen = true"
    >
        <x-x-data-table 
            endpoint="/users/data"
            id="usersTable"
            defaultSort="name"
            defaultDirection="asc"
        >
            <!-- Custom Filters Slot -->
            <x-slot name="filterSlot">
                <!-- Filter Role -->
                <select 
                    @change="setFilter('role', $event.target.value)"
                    class="rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] px-2 py-1.5 text-xs text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                >
                    <option value="">Semua Peran</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>

                <!-- Filter Status Aktif -->
                <select 
                    @change="setFilter('is_active', $event.target.value)"
                    class="rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] px-2 py-1.5 text-xs text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                >
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>

                <!-- Filter Seksi -->
                <select 
                    @change="setFilter('section_id', $event.target.value)"
                    class="rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] px-2 py-1.5 text-xs text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                >
                    <option value="">Semua Seksi</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
            </x-slot>

            <x-slot name="header">
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('id')">
                    <div class="flex items-center gap-1">
                        <span>#</span>
                        <span x-show="sort === 'id'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('name')">
                    <div class="flex items-center gap-1">
                        <span>Nama Pegawai</span>
                        <span x-show="sort === 'name'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('username')">
                    <div class="flex items-center gap-1">
                        <span>Username</span>
                        <span x-show="sort === 'username'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('nip')">
                    <div class="flex items-center gap-1">
                        <span>NIP</span>
                        <span x-show="sort === 'nip'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 cursor-pointer" @click="sortBy('role')">
                    <div class="flex items-center gap-1">
                        <span>Peran</span>
                        <span x-show="sort === 'role'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3">Seksi</th>
                <th class="px-4 py-3 cursor-pointer text-center" @click="sortBy('is_active')">
                    <div class="flex items-center justify-center gap-1">
                        <span>Status</span>
                        <span x-show="sort === 'is_active'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </x-slot>

            <template x-for="item in items" :key="item.id">
                <tr class="hover:bg-[var(--color-surface)]/50 transition">
                    <td class="px-4 py-3 font-mono text-slate-500" x-text="item.id"></td>
                    <td class="px-4 py-3 font-semibold text-[var(--color-text-main)]">
                        <div class="flex items-center gap-2">
                            <span x-text="item.name"></span>
                            <span x-show="item.is_self" class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                Anda
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-slate-600" x-text="item.username"></td>
                    <td class="px-4 py-3 text-slate-500" x-text="item.nip"></td>
                    <td class="px-4 py-3">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                            :class="{
                                'bg-purple-100 text-purple-800 border border-purple-200': item.role === 'super_admin',
                                'bg-sky-100 text-sky-800 border border-sky-200': item.role === 'archivist',
                                'bg-slate-100 text-slate-800 border border-slate-200': item.role === 'employee'
                            }"
                            x-text="item.role_label"
                        ></span>
                    </td>
                    <td class="px-4 py-3 text-slate-600" x-text="item.section_name"></td>
                    <td class="px-4 py-3 text-center">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="item.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            x-text="item.is_active ? 'Aktif' : 'Nonaktif'"
                        ></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Edit Button -->
                            <button 
                                type="button" 
                                @click="openEdit(item)"
                                class="p-1 rounded text-[var(--color-primary)] hover:bg-[var(--color-surface)] transition"
                                title="Ubah Data Pengguna"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            <!-- Reset Password Button -->
                            <button 
                                type="button" 
                                @click="openReset(item)"
                                class="p-1 rounded text-amber-600 hover:bg-amber-50 transition"
                                title="Reset Kata Sandi"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </button>

                            <!-- Delete Button (Guard: not self & no loans) -->
                            <template x-if="item.can_delete">
                                <button 
                                    type="button" 
                                    @click="openDelete(item)"
                                    class="p-1 rounded text-[var(--color-danger)] hover:bg-rose-50 transition"
                                    title="Hapus Pengguna"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </template>
                            <template x-if="!item.can_delete">
                                <span 
                                    class="p-1 text-slate-300 cursor-not-allowed" 
                                    :title="item.is_self ? 'Anda tidak dapat menghapus akun Anda sendiri' : 'Pengguna memiliki riwayat peminjaman (cukup dinonaktifkan)'"
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

        <!-- Modal Tambah Pengguna Baru -->
        <div 
            x-show="createModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="createModalOpen = false"
                class="w-full max-w-lg bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Tambah Pengguna Baru</h3>
                    <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form method="POST" action="/users" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-input 
                            label="Nama Lengkap" 
                            name="name" 
                            placeholder="Nama pegawai" 
                            required 
                        />

                        <x-input 
                            label="Username" 
                            name="username" 
                            placeholder="username_pegawai" 
                            required 
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-input 
                            label="NIP (Nomor Induk Pegawai)" 
                            name="nip" 
                            placeholder="19900101..." 
                        />

                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Peran / Hak Akses <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="role" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Seksi Organisasi
                            </label>
                            <select 
                                name="section_id" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Seksi --</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Status Akun
                            </label>
                            <select 
                                name="is_active" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-[var(--color-surface-border)]">
                        <x-input 
                            label="Kata Sandi (Min. 8 Karakter)" 
                            name="password" 
                            type="password" 
                            placeholder="••••••••" 
                            required 
                        />

                        <x-input 
                            label="Konfirmasi Kata Sandi" 
                            name="password_confirmation" 
                            type="password" 
                            placeholder="••••••••" 
                            required 
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button type="button" variant="secondary" size="sm" @click="createModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Simpan Pengguna
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Pengguna -->
        <div 
            x-show="editModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="editModalOpen = false"
                class="w-full max-w-lg bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Ubah Data Pengguna</h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form :action="'/users/' + editForm.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                x-model="editForm.name" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="username" 
                                x-model="editForm.username" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                NIP
                            </label>
                            <input 
                                type="text" 
                                name="nip" 
                                x-model="editForm.nip" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Peran / Hak Akses <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="role" 
                                x-model="editForm.role" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Seksi Organisasi
                            </label>
                            <select 
                                name="section_id" 
                                x-model="editForm.section_id" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Seksi --</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Status Akun
                            </label>
                            <select 
                                name="is_active" 
                                x-model="editForm.is_active" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option :value="true">Aktif</option>
                                <option :value="false">Nonaktif</option>
                            </select>
                        </div>
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

        <!-- Modal Reset Password Pengguna -->
        <div 
            x-show="resetModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="resetModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Reset Kata Sandi Pengguna</h3>
                    <button type="button" @click="resetModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <p class="text-xs text-[var(--color-text-muted)]">
                    Reset kata sandi untuk pengguna <strong class="text-[var(--color-text-main)]" x-text="resetForm.name"></strong> (<span class="font-mono" x-text="resetForm.username"></span>).
                </p>

                <form :action="'/users/' + resetForm.id + '/reset-password'" method="POST" class="space-y-4">
                    @csrf
                    
                    <x-input 
                        label="Kata Sandi Baru (Min. 8 Karakter)" 
                        name="password" 
                        type="password" 
                        placeholder="••••••••" 
                        required 
                    />

                    <x-input 
                        label="Konfirmasi Kata Sandi Baru" 
                        name="password_confirmation" 
                        type="password" 
                        placeholder="••••••••" 
                        required 
                    />

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button type="button" variant="secondary" size="sm" @click="resetModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Reset Kata Sandi
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Pengguna -->
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
                        <h3 class="text-sm font-bold text-[var(--color-text-main)]">Konfirmasi Hapus Pengguna</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-xs text-[var(--color-text-main)] leading-relaxed">
                    Apakah Anda yakin ingin menghapus akun pegawai <strong x-text="deleteTarget.name"></strong> (<span class="font-mono" x-text="deleteTarget.username"></span>)?
                </p>

                <form :action="'/users/' + deleteTarget.id" method="POST" class="flex justify-end gap-2 pt-2">
                    @csrf
                    @method('DELETE')
                    <x-button type="button" variant="secondary" size="sm" @click="deleteModalOpen = false">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="danger" size="sm">
                        Ya, Hapus Pengguna
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
