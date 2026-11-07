<x-app-layout title="Katalog Berkas Arsip">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
                    Katalog Berkas Arsip
                </h1>
                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                    Pencarian, pemantauan status fisik, lokasi rak penyimpanan, dan pengelolaan berkas arsip pertanahan
                </p>
            </div>
            @if(auth()->user()->canManageArchives())
                <button 
                    type="button" 
                    @click="$dispatch('open-create-doc-modal')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90 transition shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Berkas</span>
                </button>
            @endif
        </div>
    </x-slot>

    <!-- Main Content Container with Alpine Scope -->
    <div 
        class="col-span-12 space-y-4"
        x-data="{
            // Filter states
            filterDistrict: '',
            filterVillage: '',
            filterDocNumber: '',
            filterLandRight: '',
            filterDocType: '',
            filterYear: '',
            filterStatus: '',
            filterVillagesList: [],
            filterVillagesLoading: false,

            // Modal states
            createModalOpen: false,
            editModalOpen: false,
            statusModalOpen: false,
            deleteModalOpen: false,
            previewModalOpen: false,

            // Form data states
            createForm: {
                district_id: '',
                village_id: '',
                villagesList: [],
                villagesLoading: false
            },
            editForm: {
                id: null,
                document_number: '',
                year: '',
                document_type_id: '',
                land_right_type_id: '',
                storage_rack_id: '',
                range_number: '',
                district_id: '',
                village_id: '',
                villagesList: [],
                villagesLoading: false
            },
            statusForm: {
                id: null,
                document_number: '',
                current_status: '',
                status: 'available'
            },
            deleteTarget: {
                id: null,
                document_number: ''
            },
            previewTarget: {
                id: null,
                document_number: '',
                filename: '',
                mime_type: '',
                url: ''
            },

            // Wilayah API Helpers
            onFilterDistrictChange() {
                this.filterVillage = '';
                this.filterVillagesList = [];
                if (!this.filterDistrict) return;

                this.filterVillagesLoading = true;
                fetch(`/api/districts/${this.filterDistrict}/villages`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.filterVillagesList = data;
                })
                .catch(e => console.error(e))
                .finally(() => {
                    this.filterVillagesLoading = false;
                });
            },

            onCreateDistrictChange() {
                this.createForm.village_id = '';
                this.createForm.villagesList = [];
                if (!this.createForm.district_id) return;

                this.createForm.villagesLoading = true;
                fetch(`/api/districts/${this.createForm.district_id}/villages`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.createForm.villagesList = data;
                })
                .catch(e => console.error(e))
                .finally(() => {
                    this.createForm.villagesLoading = false;
                });
            },

            onEditDistrictChange(selectedVillageId = '') {
                this.editForm.village_id = selectedVillageId;
                this.editForm.villagesList = [];
                if (!this.editForm.district_id) return;

                this.editForm.villagesLoading = true;
                fetch(`/api/districts/${this.editForm.district_id}/villages`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.editForm.villagesList = data;
                    if (selectedVillageId) {
                        this.editForm.village_id = selectedVillageId;
                    }
                })
                .catch(e => console.error(e))
                .finally(() => {
                    this.editForm.villagesLoading = false;
                });
            },

            // Modal Opener Methods
            openEdit(item) {
                this.editForm = {
                    id: item.id,
                    document_number: item.document_number,
                    year: item.raw_year || '',
                    document_type_id: item.document_type.id,
                    land_right_type_id: item.land_right_type.id,
                    storage_rack_id: item.storage_rack.id,
                    range_number: item.raw_range_number || '',
                    district_id: item.district.id,
                    village_id: item.village.id,
                    villagesList: [],
                    villagesLoading: false
                };
                this.onEditDistrictChange(item.village.id);
                this.editModalOpen = true;
            },

            openStatusModal(item) {
                this.statusForm = {
                    id: item.id,
                    document_number: item.document_number,
                    current_status: item.status,
                    status: (item.status === 'lost' || item.status === 'invalid') ? item.status : 'available'
                };
                this.statusModalOpen = true;
            },

            openDelete(item) {
                this.deleteTarget = {
                    id: item.id,
                    document_number: item.document_number
                };
                this.deleteModalOpen = true;
            },

            openPreview(item) {
                this.previewTarget = {
                    id: item.id,
                    document_number: item.document_number,
                    filename: item.original_filename || 'Dokumen ' + item.document_number,
                    mime_type: item.mime_type || '',
                    url: `/documents/${item.id}/file`
                };
                this.previewModalOpen = true;
            },

            applyFilters(tableComponent) {
                tableComponent.setFilter('district_id', this.filterDistrict);
                tableComponent.setFilter('village_id', this.filterVillage);
                tableComponent.setFilter('document_number', this.filterDocNumber);
                tableComponent.setFilter('land_right_type_id', this.filterLandRight);
                tableComponent.setFilter('document_type_id', this.filterDocType);
                tableComponent.setFilter('year', this.filterYear);
                tableComponent.setFilter('status', this.filterStatus);
            },

            resetAllFilters(tableComponent) {
                this.filterDistrict = '';
                this.filterVillage = '';
                this.filterDocNumber = '';
                this.filterLandRight = '';
                this.filterDocType = '';
                this.filterYear = '';
                this.filterStatus = '';
                this.filterVillagesList = [];
                tableComponent.resetFilters();
            }
        }"
        @open-create-doc-modal.window="createModalOpen = true"
    >
        <!-- Filter Card -->
        <div class="rounded-xl border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] p-4 shadow-2xs">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-[var(--color-surface-border)]">
                <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-primary)] flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter Pencarian Katalog
                </span>
                <span class="text-[11px] text-[var(--color-text-muted)]">Kombinasikan filter untuk pencarian akurat</span>
            </div>

            <!-- Filter Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Kecamatan -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">Kecamatan</label>
                    <select 
                        x-model="filterDistrict" 
                        @change="onFilterDistrictChange()"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    >
                        <option value="">-- Semua Kecamatan --</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}">{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Desa (Berantai) -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">
                        Desa / Kelurahan
                        <span x-show="filterVillagesLoading" class="text-[10px] text-sky-600 font-normal">(Memuat...)</span>
                    </label>
                    <select 
                        x-model="filterVillage" 
                        :disabled="!filterDistrict || filterVillagesLoading"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <option value="">-- Semua Desa --</option>
                        <template x-for="vil in filterVillagesList" :key="vil.id">
                            <option :value="vil.id" x-text="vil.name"></option>
                        </template>
                    </select>
                </div>

                <!-- No. Berkas Parsial -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">No. Berkas</label>
                    <input 
                        type="text" 
                        x-model="filterDocNumber" 
                        placeholder="Contoh: 1234" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    />
                </div>

                <!-- Jenis Hak -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">Jenis Hak</label>
                    <select 
                        x-model="filterLandRight" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    >
                        <option value="">-- Semua Hak --</option>
                        @foreach($landRightTypes as $right)
                            <option value="{{ $right->id }}">{{ $right->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jenis Berkas -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">Jenis Berkas</label>
                    <select 
                        x-model="filterDocType" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    >
                        <option value="">-- Semua Jenis --</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tahun -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">Tahun</label>
                    <input 
                        type="number" 
                        x-model="filterYear" 
                        placeholder="YYYY" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    />
                </div>
            </div>

            <!-- Filter Action Buttons -->
            <div class="flex items-center justify-between pt-3 mt-3 border-t border-[var(--color-surface-border)]">
                <!-- Status Filter Radio/Select -->
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-semibold text-[var(--color-text-muted)]">Status:</span>
                    <select 
                        x-model="filterStatus"
                        class="px-2 py-1 text-xs rounded-md border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none"
                    >
                        <option value="">Semua Status</option>
                        <option value="available">Tersedia</option>
                        <option value="booked">Booking</option>
                        <option value="borrowed">Dipinjam</option>
                        <option value="lost">Hilang</option>
                        <option value="invalid">Tidak Berlaku</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Tombol Reset Filter -->
                    <button 
                        type="button" 
                        @click="resetAllFilters($el.closest('.col-span-12').querySelector('#documentsTable').__x.$data)"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] hover:bg-[var(--color-surface)] transition"
                    >
                        Reset Filter
                    </button>

                    <!-- Tombol Terapkan Filter -->
                    <button 
                        type="button" 
                        @click="applyFilters($el.closest('.col-span-12').querySelector('#documentsTable').__x.$data)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90 transition shadow-2xs flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Data Server-Side -->
        <x-x-data-table 
            endpoint="/documents/data"
            id="documentsTable"
            defaultSort="id"
            defaultDirection="desc"
            perPage="10"
        >
            <x-slot name="header">
                <th class="px-3 py-3 cursor-pointer" @click="sortBy('document_number')">
                    <div class="flex items-center gap-1">
                        <span>No. Berkas</span>
                        <span x-show="sort === 'document_number'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-2 py-3 cursor-pointer text-center" @click="sortBy('year')">
                    <div class="flex items-center justify-center gap-1">
                        <span>Tahun</span>
                        <span x-show="sort === 'year'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3">Rak</th>
                <th class="px-3 py-3">Jenis Berkas</th>
                <th class="px-3 py-3">Jenis Hak</th>
                <th class="px-3 py-3 cursor-pointer" @click="sortBy('range_number')">
                    <div class="flex items-center gap-1">
                        <span>No. Urut</span>
                        <span x-show="sort === 'range_number'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3">Kecamatan</th>
                <th class="px-3 py-3">Desa</th>
                <th class="px-3 py-3 cursor-pointer" @click="sortBy('created_at')">
                    <div class="flex items-center gap-1">
                        <span>Waktu Input</span>
                        <span x-show="sort === 'created_at'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3 cursor-pointer text-center" @click="sortBy('status')">
                    <div class="flex items-center justify-center gap-1">
                        <span>Status</span>
                        <span x-show="sort === 'status'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3 text-right">Aksi</th>
            </x-slot>

            <template x-for="item in items" :key="item.id">
                <tr class="hover:bg-[var(--color-surface)]/50 transition">
                    <!-- Nomor Berkas -->
                    <td class="px-3 py-3 font-semibold text-[var(--color-text-main)]">
                        <div class="flex items-center gap-1.5">
                            <span x-text="item.document_number"></span>
                            <template x-if="item.has_file">
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" title="File scan terunggah"></span>
                            </template>
                        </div>
                    </td>

                    <!-- Tahun -->
                    <td class="px-2 py-3 text-center text-slate-600 font-mono" x-text="item.year"></td>

                    <!-- Lokasi Rak -->
                    <td class="px-3 py-3 text-[var(--color-text-main)]">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-800" x-text="item.storage_rack.name"></span>
                    </td>

                    <!-- Jenis Berkas -->
                    <td class="px-3 py-3 text-[var(--color-text-main)]" x-text="item.document_type.name"></td>

                    <!-- Jenis Hak -->
                    <td class="px-3 py-3 text-[var(--color-text-main)]" x-text="item.land_right_type.name"></td>

                    <!-- Nomor Urut / Range -->
                    <td class="px-3 py-3 font-mono text-slate-500 text-[11px]" x-text="item.range_number"></td>

                    <!-- Kecamatan -->
                    <td class="px-3 py-3 text-[var(--color-text-main)]" x-text="item.district.name"></td>

                    <!-- Desa -->
                    <td class="px-3 py-3 text-[var(--color-text-main)]" x-text="item.village.name"></td>

                    <!-- Waktu Input -->
                    <td class="px-3 py-3 text-slate-500 text-[11px]" x-text="item.created_at"></td>

                    <!-- Status Dokumen -->
                    <td class="px-3 py-3 text-center">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border"
                            :class="item.status_badge"
                            x-text="item.status_label"
                        ></span>
                    </td>

                    <!-- Aksi -->
                    <td class="px-3 py-3 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Pratinjau File Scan -->
                            <template x-if="item.has_file">
                                <button 
                                    type="button" 
                                    @click="openPreview(item)"
                                    class="p-1 rounded text-sky-600 hover:bg-sky-50 transition"
                                    title="Pratinjau Berkas Pindaian"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </template>

                            <!-- Ubah Data Berkas (Staf Arsip) -->
                            <template x-if="item.can.edit">
                                <button 
                                    type="button" 
                                    @click="openEdit(item)"
                                    class="p-1 rounded text-[var(--color-primary)] hover:bg-[var(--color-surface)] transition"
                                    title="Ubah Berkas"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </template>

                            <!-- Ubah Status Fisik Berkas (Staf Arsip, jika tidak ada loan aktif) -->
                            <template x-if="item.can.update_status">
                                <button 
                                    type="button" 
                                    @click="openStatusModal(item)"
                                    class="p-1 rounded text-amber-600 hover:bg-amber-50 transition"
                                    title="Ubah Status Fisik"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                            </template>

                            <!-- Hapus Dokumen (Staf Arsip, jika tidak ada loan aktif) -->
                            <template x-if="item.can.delete">
                                <button 
                                    type="button" 
                                    @click="openDelete(item)"
                                    class="p-1 rounded text-[var(--color-danger)] hover:bg-rose-50 transition"
                                    title="Hapus Dokumen"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </td>
                </tr>
            </template>
        </x-x-data-table>

        <!-- ========================================== -->
        <!-- MODAL TAMBAH BERKAS -->
        <!-- ========================================== -->
        <div 
            x-show="createModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="createModalOpen = false"
                class="w-full max-w-2xl bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Tambah Berkas Arsip Baru</h3>
                    <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form method="POST" action="/documents" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nomor Berkas -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Nomor Berkas <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="document_number" 
                                required 
                                placeholder="Contoh: 00123" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <!-- Tahun -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Tahun (Opsional)
                            </label>
                            <input 
                                type="number" 
                                name="year" 
                                placeholder="Contoh: 2024" 
                                min="1900" 
                                max="2100" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <!-- Jenis Berkas -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Jenis Berkas <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="document_type_id" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Jenis Berkas --</option>
                                @foreach($documentTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Jenis Hak -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Jenis Hak <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="land_right_type_id" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Jenis Hak --</option>
                                @foreach($landRightTypes as $right)
                                    <option value="{{ $right->id }}">{{ $right->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Lokasi Rak -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Rak Penyimpanan <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="storage_rack_id" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Rak --</option>
                                @foreach($storageRacks as $rack)
                                    <option value="{{ $rack->id }}">{{ $rack->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Nomor Urut / Range -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Nomor Urut / Range (Opsional)
                            </label>
                            <input 
                                type="text" 
                                name="range_number" 
                                placeholder="Contoh: 01-10" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <!-- Wilayah Kecamatan -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Kecamatan <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="district_id" 
                                x-model="createForm.district_id" 
                                @change="onCreateDistrictChange()"
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Wilayah Desa (Berantai) -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Desa / Kelurahan <span class="text-rose-500">*</span>
                                <span x-show="createForm.villagesLoading" class="text-[10px] text-sky-600 font-normal">(Memuat...)</span>
                            </label>
                            <select 
                                name="village_id" 
                                x-model="createForm.village_id" 
                                :disabled="!createForm.district_id || createForm.villagesLoading"
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <option value="">-- Pilih Desa --</option>
                                <template x-for="vil in createForm.villagesList" :key="vil.id">
                                    <option :value="vil.id" x-text="vil.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Upload Berkas Pindaian (Scan) -->
                    <div class="pt-2 border-t border-[var(--color-surface-border)]">
                        <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                            Berkas Pindaian Scan (Opsional, PDF/JPG/PNG maks 20 MB)
                        </label>
                        <input 
                            type="file" 
                            name="file" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[var(--color-surface)] file:text-[var(--color-primary)] hover:file:opacity-90"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <x-button type="button" variant="secondary" size="sm" @click="createModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Simpan Berkas
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL UBAH BERKAS -->
        <!-- ========================================== -->
        <div 
            x-show="editModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="editModalOpen = false"
                class="w-full max-w-2xl bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Ubah Berkas Arsip</h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form :action="'/documents/' + editForm.id" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nomor Berkas -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Nomor Berkas <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="document_number" 
                                x-model="editForm.document_number" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <!-- Tahun -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Tahun (Opsional)
                            </label>
                            <input 
                                type="number" 
                                name="year" 
                                x-model="editForm.year" 
                                min="1900" 
                                max="2100" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <!-- Jenis Berkas -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Jenis Berkas <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="document_type_id" 
                                x-model="editForm.document_type_id" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Jenis Berkas --</option>
                                @foreach($documentTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Jenis Hak -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Jenis Hak <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="land_right_type_id" 
                                x-model="editForm.land_right_type_id" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Jenis Hak --</option>
                                @foreach($landRightTypes as $right)
                                    <option value="{{ $right->id }}">{{ $right->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Lokasi Rak -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Rak Penyimpanan <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="storage_rack_id" 
                                x-model="editForm.storage_rack_id" 
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Rak --</option>
                                @foreach($storageRacks as $rack)
                                    <option value="{{ $rack->id }}">{{ $rack->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Nomor Urut / Range -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Nomor Urut / Range (Opsional)
                            </label>
                            <input 
                                type="text" 
                                name="range_number" 
                                x-model="editForm.range_number" 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            />
                        </div>

                        <!-- Wilayah Kecamatan -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Kecamatan <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                name="district_id" 
                                x-model="editForm.district_id" 
                                @change="onEditDistrictChange()"
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                            >
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Wilayah Desa (Berantai) -->
                        <div>
                            <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                                Desa / Kelurahan <span class="text-rose-500">*</span>
                                <span x-show="editForm.villagesLoading" class="text-[10px] text-sky-600 font-normal">(Memuat...)</span>
                            </label>
                            <select 
                                name="village_id" 
                                x-model="editForm.village_id" 
                                :disabled="!editForm.district_id || editForm.villagesLoading"
                                required 
                                class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <option value="">-- Pilih Desa --</option>
                                <template x-for="vil in editForm.villagesList" :key="vil.id">
                                    <option :value="vil.id" x-text="vil.name" :selected="vil.id === editForm.village_id"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Ganti Berkas Pindaian -->
                    <div class="pt-2 border-t border-[var(--color-surface-border)]">
                        <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                            Ganti Berkas Pindaian Scan (Opsional, file lama akan diganti)
                        </label>
                        <input 
                            type="file" 
                            name="file" 
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[var(--color-surface)] file:text-[var(--color-primary)] hover:file:opacity-90"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
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

        <!-- ========================================== -->
        <!-- MODAL UBAH STATUS FISIK DOKUMEN -->
        <!-- ========================================== -->
        <div 
            x-show="statusModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="statusModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-[var(--color-surface-border)]">
                    <h3 class="text-base font-bold text-[var(--color-primary)]">Ubah Status Fisik Dokumen</h3>
                    <button type="button" @click="statusModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <p class="text-xs text-[var(--color-text-main)]">
                    Perubahan status fisik untuk nomor berkas: <strong x-text="statusForm.document_number"></strong>
                </p>

                <form :action="'/documents/' + statusForm.id + '/status'" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold text-[var(--color-text-main)] mb-1">
                            Pilih Status Fisik Baru <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            name="status" 
                            x-model="statusForm.status" 
                            required 
                            class="w-full px-3 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                        >
                            <option value="available">Tersedia (Available) - Berkas ada di rak</option>
                            <option value="lost">Hilang (Lost) - Berkas tidak ditemukan di rak</option>
                            <option value="invalid">Rusak / Tidak Berlaku (Invalid)</option>
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">
                            Catatan: Perubahan manual hanya berlaku saat dokumen tidak dalam status dipinjam atau di-booking.
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button type="button" variant="secondary" size="sm" @click="statusModalOpen = false">
                            Batal
                        </x-button>
                        <x-button type="submit" variant="primary" size="sm">
                            Perbarui Status
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL PRATINJAU FILE SCAN -->
        <!-- ========================================== -->
        <div 
            x-show="previewModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-2 sm:p-4"
            style="display: none;"
        >
            <div 
                @click.outside="previewModalOpen = false"
                class="w-full max-w-4xl max-h-[92vh] flex flex-col bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-2xl overflow-hidden"
            >
                <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--color-surface-border)] bg-[var(--color-surface)]/50">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-[var(--color-primary)]">
                            Pratinjau Berkas: <span x-text="previewTarget.document_number"></span>
                        </span>
                        <span class="text-xs text-slate-500 font-mono" x-text="'(' + previewTarget.filename + ')'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a 
                            :href="previewTarget.url" 
                            target="_blank" 
                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-sky-700 bg-sky-50 hover:bg-sky-100 transition"
                        >
                            <span>Buka di Tab Baru</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                        <button type="button" @click="previewModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg px-1">&times;</button>
                    </div>
                </div>

                <!-- Preview Area -->
                <div class="flex-1 min-h-[500px] bg-slate-900 flex items-center justify-center relative">
                    <template x-if="previewModalOpen">
                        <iframe 
                            :src="previewTarget.url" 
                            class="w-full h-[650px] border-0"
                            title="Pratinjau Berkas"
                        ></iframe>
                    </template>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL KONFIRMASI HAPUS DOKUMEN -->
        <!-- ========================================== -->
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
                        <h3 class="text-sm font-bold text-[var(--color-text-main)]">Konfirmasi Hapus Dokumen</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Penghapusan menggunakan soft delete.</p>
                    </div>
                </div>

                <p class="text-xs text-[var(--color-text-main)] leading-relaxed">
                    Apakah Anda yakin ingin menghapus berkas nomor <strong x-text="deleteTarget.document_number"></strong>?
                </p>

                <form :action="'/documents/' + deleteTarget.id" method="POST" class="flex justify-end gap-2 pt-2">
                    @csrf
                    @method('DELETE')
                    <x-button type="button" variant="secondary" size="sm" @click="deleteModalOpen = false">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="danger" size="sm">
                        Ya, Hapus Dokumen
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
