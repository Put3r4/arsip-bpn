<x-app-layout title="Peminjaman Berkas Arsip">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--color-primary)]">
                    Peminjaman Berkas Arsip
                </h1>
                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                    @if(auth()->user()->canManageArchives())
                        Monitoring persetujuan loket arsip, penyerahan berkas fisik, dan verifikasi pengembalian ke rak penyimpanan.
                    @else
                        Daftar permohonan booking peminjaman dan berkas arsip yang sedang Anda bawa.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a 
                    href="/documents" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90 transition shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Cari Berkas di Katalog</span>
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Main Content Container with Alpine Scope -->
    <div 
        class="col-span-12 space-y-4"
        x-data="{
            // Active Tab: 'active' atau 'returned'
            activeTab: '{{ $tab === 'returned' ? 'returned' : 'active' }}',
            statusFilter: '{{ $status }}',
            searchFilter: '',
            activityFilter: '',

            // Modals
            approveModalOpen: false,
            returnModalOpen: false,
            cancelModalOpen: false,
            previewModalOpen: false,

            targetLoan: {
                id: null,
                document_number: '',
                borrower_name: '',
                status: '',
                rack: ''
            },

            previewTarget: {
                url: '',
                document_number: ''
            },

            switchTab(tabName) {
                this.activeTab = tabName;
                this.statusFilter = '';
                this.applyFilters();
            },

            applyFilters() {
                const table = document.querySelector('#loansTable')?.__x?.$data;
                if (!table) return;

                table.setFilter('tab', this.activeTab);
                table.setFilter('status', this.statusFilter);
                table.setFilter('search', this.searchFilter);
                table.setFilter('service_activity_id', this.activityFilter);
            },

            resetFilters() {
                this.statusFilter = '';
                this.searchFilter = '';
                this.activityFilter = '';
                const table = document.querySelector('#loansTable')?.__x?.$data;
                if (!table) return;

                table.resetFilters();
                table.setFilter('tab', this.activeTab);
            },

            openApprove(item) {
                this.targetLoan = {
                    id: item.id,
                    document_number: item.document.document_number,
                    borrower_name: item.borrower.name,
                    status: item.status,
                    rack: item.document.storage_rack
                };
                this.approveModalOpen = true;
            },

            openReturn(item) {
                this.targetLoan = {
                    id: item.id,
                    document_number: item.document.document_number,
                    borrower_name: item.borrower.name,
                    status: item.status,
                    rack: item.document.storage_rack
                };
                this.returnModalOpen = true;
            },

            openCancel(item) {
                this.targetLoan = {
                    id: item.id,
                    document_number: item.document.document_number,
                    borrower_name: item.borrower.name,
                    status: item.status,
                    rack: item.document.storage_rack
                };
                this.cancelModalOpen = true;
            },

            openPreview(item) {
                if (!item.document.has_file) return;
                this.previewTarget = {
                    url: `/documents/${item.document.id}/file`,
                    document_number: item.document.document_number
                };
                this.previewModalOpen = true;
            }
        }"
        x-init="$nextTick(() => { applyFilters(); })"
    >
        <!-- Navigasi Tab (Aktif vs Riwayat Pengembalian) -->
        <div class="flex border-b border-[var(--color-surface-border)] gap-2">
            <button 
                type="button"
                @click="switchTab('active')"
                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition"
                :class="activeTab === 'active' 
                    ? 'border-[var(--color-primary)] text-[var(--color-primary)]' 
                    : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-main)]'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Peminjaman Aktif (Pending & Dipinjam)</span>
            </button>

            <button 
                type="button"
                @click="switchTab('returned')"
                class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition"
                :class="activeTab === 'returned' 
                    ? 'border-[var(--color-primary)] text-[var(--color-primary)]' 
                    : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-main)]'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Riwayat Pengembalian & Arsip Selesai</span>
            </button>
        </div>

        <!-- Filter Bar Card -->
        <div class="rounded-xl border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] p-4 shadow-2xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Pencarian Kata Kunci -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">
                        Pencarian
                    </label>
                    <input 
                        type="text" 
                        x-model="searchFilter" 
                        placeholder="No. berkas, peminjam, catatan..." 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    />
                </div>

                <!-- Sub-Filter Status Berdasarkan Tab -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">
                        Status Transaksi
                    </label>
                    <select 
                        x-model="statusFilter"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    >
                        <template x-if="activeTab === 'active'">
                            <optgroup label="Peminjaman Aktif">
                                <option value="">Semua Status Aktif</option>
                                <option value="pending">Menunggu Persetujuan (Pending)</option>
                                <option value="borrowed">Sedang Dipinjam (Borrowed)</option>
                            </optgroup>
                        </template>
                        <template x-if="activeTab === 'returned'">
                            <optgroup label="Riwayat Pengembalian">
                                <option value="">Semua Riwayat</option>
                                <option value="returned">Sudah Dikembalikan (Returned)</option>
                                <option value="cancelled">Dibatalkan (Cancelled)</option>
                            </optgroup>
                        </template>
                    </select>
                </div>

                <!-- Filter Kegiatan Pelayanan -->
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--color-text-muted)] mb-1">
                        Kegiatan Pelayanan
                    </label>
                    <select 
                        x-model="activityFilter"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                    >
                        <option value="">Semua Kegiatan</option>
                        @foreach($serviceActivities as $act)
                            <option value="{{ $act->id }}">{{ $act->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tombol Aksi Filter -->
                <div class="flex items-end gap-2">
                    <button 
                        type="button" 
                        @click="resetFilters()"
                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] hover:bg-[var(--color-surface)] transition text-center"
                    >
                        Reset
                    </button>
                    <button 
                        type="button" 
                        @click="applyFilters()"
                        class="flex-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[var(--color-primary)] text-[var(--color-primary-contrast)] hover:opacity-90 transition shadow-2xs flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Data Server-Side untuk Peminjaman -->
        <x-x-data-table 
            endpoint="/loans/data"
            id="loansTable"
            defaultSort="id"
            defaultDirection="desc"
            perPage="10"
        >
            <x-slot name="header">
                <th class="px-3 py-3 cursor-pointer text-center" @click="sortBy('id')">
                    <div class="flex items-center justify-center gap-1">
                        <span>No. Pinjam</span>
                        <span x-show="sort === 'id'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3">Berkas & Lokasi Rak</th>
                <th class="px-3 py-3">Peminjam & Seksi</th>
                <th class="px-3 py-3">Kegiatan Pelayanan</th>
                <th class="px-3 py-3 cursor-pointer" @click="sortBy('loan_date')">
                    <div class="flex items-center gap-1">
                        <span>Tgl Pinjam</span>
                        <span x-show="sort === 'loan_date'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3 cursor-pointer" @click="sortBy('return_date')">
                    <div class="flex items-center gap-1">
                        <span>Tgl Kembali</span>
                        <span x-show="sort === 'return_date'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3 cursor-pointer text-center" @click="sortBy('status')">
                    <div class="flex items-center justify-center gap-1">
                        <span>Status</span>
                        <span x-show="sort === 'status'" x-text="direction === 'asc' ? '▲' : '▼'"></span>
                    </div>
                </th>
                <th class="px-3 py-3">Keterangan</th>
                <th class="px-3 py-3 text-right">Aksi</th>
            </x-slot>

            <template x-for="item in items" :key="item.id">
                <tr class="hover:bg-[var(--color-surface)]/50 transition">
                    <!-- ID Pinjam -->
                    <td class="px-3 py-3 text-center font-mono font-bold text-slate-700 text-xs" x-text="'#' + item.id"></td>

                    <!-- Berkas & Rak -->
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-1.5 font-semibold text-[var(--color-text-main)]">
                            <span x-text="item.document.document_number"></span>
                            <template x-if="item.document.has_file">
                                <button 
                                    type="button" 
                                    @click="openPreview(item)" 
                                    class="text-sky-600 hover:text-sky-800"
                                    title="Pratinjau Berkas Pindaian"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Rak: <span class="font-semibold text-slate-700" x-text="item.document.storage_rack"></span>
                            <span x-show="item.document.range_number && item.document.range_number !== '-'" x-text="' (' + item.document.range_number + ')'"></span>
                        </div>
                    </td>

                    <!-- Peminjam & Seksi -->
                    <td class="px-3 py-3">
                        <div class="font-medium text-[var(--color-text-main)]" x-text="item.borrower.name"></div>
                        <div class="text-[11px] text-slate-500" x-text="item.borrower.section"></div>
                    </td>

                    <!-- Kegiatan Pelayanan -->
                    <td class="px-3 py-3 text-[var(--color-text-main)]" x-text="item.service_activity.name"></td>

                    <!-- Tgl Pinjam -->
                    <td class="px-3 py-3 font-mono text-xs text-slate-600" x-text="item.loan_date"></td>

                    <!-- Tgl Kembali -->
                    <td class="px-3 py-3 font-mono text-xs text-slate-600">
                        <span x-text="item.return_date"></span>
                    </td>

                    <!-- Status Badge -->
                    <td class="px-3 py-3 text-center">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border"
                            :class="item.status_badge"
                            x-text="item.status_label"
                        ></span>
                    </td>

                    <!-- Keterangan -->
                    <td class="px-3 py-3 text-[11px] text-slate-600 italic max-w-xs truncate" x-text="item.notes" :title="item.notes"></td>

                    <!-- Tombol Aksi Baris -->
                    <td class="px-3 py-3 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Approve (ACC) - Staf Arsip saat pending -->
                            <template x-if="item.can.approve">
                                <button 
                                    type="button" 
                                    @click="openApprove(item)"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-2xs"
                                    title="Setujui Peminjaman (ACC)"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Setujui</span>
                                </button>
                            </template>

                            <!-- Return (Kembalikan) - Staf Arsip saat borrowed -->
                            <template x-if="item.can.return">
                                <button 
                                    type="button" 
                                    @click="openReturn(item)"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-sky-600 text-white hover:bg-sky-700 transition shadow-2xs"
                                    title="Proses Pengembalian Berkas"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    <span>Kembalikan</span>
                                </button>
                            </template>

                            <!-- Cancel (Batalkan) - Staf atau Pemilik saat pending -->
                            <template x-if="item.can.cancel">
                                <button 
                                    type="button" 
                                    @click="openCancel(item)"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold border border-rose-300 text-rose-700 hover:bg-rose-50 transition"
                                    title="Batalkan Booking"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Batalkan</span>
                                </button>
                            </template>

                            <template x-if="!item.can.approve && !item.can.return && !item.can.cancel">
                                <span class="text-slate-400 text-xs">-</span>
                            </template>
                        </div>
                    </td>
                </tr>
            </template>
        </x-x-data-table>

        <!-- ========================================== -->
        <!-- MODAL KONFIRMASI APPROVE (ACC) PINJAM -->
        <!-- ========================================== -->
        <div 
            x-show="approveModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="approveModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center gap-3 text-emerald-600">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[var(--color-text-main)]">Persetujuan Peminjaman (ACC)</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Penyerahan berkas fisik warkah di loket arsip</p>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-[var(--color-surface)] border border-[var(--color-surface-border)] space-y-1 text-xs">
                    <p>No. Berkas: <strong class="text-[var(--color-primary)] font-bold" x-text="targetLoan.document_number"></strong></p>
                    <p>Peminjam: <strong x-text="targetLoan.borrower_name"></strong></p>
                    <p>Lokasi Rak: <span class="font-semibold" x-text="targetLoan.rack"></span></p>
                </div>

                <p class="text-xs text-[var(--color-text-main)] leading-relaxed">
                    Pastikan berkas fisik telah diserahkan langsung kepada pegawai yang bersangkutan. Status berkas akan berubah menjadi <strong>Dipinjam</strong>.
                </p>

                <form :action="'/loans/' + targetLoan.id + '/approve'" method="POST" class="flex justify-end gap-2 pt-2">
                    @csrf
                    <x-button type="button" variant="secondary" size="sm" @click="approveModalOpen = false">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" size="sm" class="bg-emerald-600 hover:bg-emerald-700">
                        Ya, Setujui & Serahkan
                    </x-button>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL KONFIRMASI PENGEMBALIAN BERKAS -->
        <!-- ========================================== -->
        <div 
            x-show="returnModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="returnModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center gap-3 text-sky-600">
                    <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[var(--color-text-main)]">Proses Pengembalian Berkas</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Verifikasi berkas fisik kembali ke rak arsip</p>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-[var(--color-surface)] border border-[var(--color-surface-border)] space-y-1 text-xs">
                    <p>No. Berkas: <strong class="text-[var(--color-primary)] font-bold" x-text="targetLoan.document_number"></strong></p>
                    <p>Peminjam: <strong x-text="targetLoan.borrower_name"></strong></p>
                    <p>Kembali ke Rak: <span class="font-semibold text-emerald-700" x-text="targetLoan.rack"></span></p>
                </div>

                <p class="text-xs text-[var(--color-text-main)] leading-relaxed">
                    Apakah Anda telah menerima berkas fisik warkah ini dalam keadaan baik dan menyimpannya kembali di rak penyimpanan?
                </p>

                <form :action="'/loans/' + targetLoan.id + '/return'" method="POST" class="flex justify-end gap-2 pt-2">
                    @csrf
                    <x-button type="button" variant="secondary" size="sm" @click="returnModalOpen = false">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" size="sm" class="bg-sky-600 hover:bg-sky-700">
                        Konfirmasi Pengembalian
                    </x-button>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL KONFIRMASI BATALKAN BOOKING -->
        <!-- ========================================== -->
        <div 
            x-show="cancelModalOpen" 
            x-transition 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <div 
                @click.outside="cancelModalOpen = false"
                class="w-full max-w-md bg-[var(--color-surface-card)] rounded-2xl border border-[var(--color-surface-border)] shadow-xl p-6 space-y-4"
            >
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[var(--color-text-main)]">Batalkan Permohonan Booking</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Pembatalan hanya untuk booking berstatus pending</p>
                    </div>
                </div>

                <p class="text-xs text-[var(--color-text-main)] leading-relaxed">
                    Apakah Anda yakin ingin membatalkan booking peminjaman untuk nomor berkas <strong x-text="targetLoan.document_number"></strong>? Status berkas akan dikembalikan menjadi <strong>Tersedia</strong>.
                </p>

                <form :action="'/loans/' + targetLoan.id + '/cancel'" method="POST" class="flex justify-end gap-2 pt-2">
                    @csrf
                    <x-button type="button" variant="secondary" size="sm" @click="cancelModalOpen = false">
                        Kembali
                    </x-button>
                    <x-button type="submit" variant="danger" size="sm">
                        Ya, Batalkan Booking
                    </x-button>
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
    </div>
</x-app-layout>
