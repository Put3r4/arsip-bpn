@props([
    'endpoint',
    'columns' => [],
    'defaultSort' => 'id',
    'defaultDirection' => 'desc',
    'perPage' => 10,
    'id' => 'dataTable',
])

<div 
    x-data="{
        endpoint: '{{ $endpoint }}',
        loading: false,
        items: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: {{ $perPage }},
            total: 0,
            from: 0,
            to: 0
        },
        search: '',
        sort: '{{ $defaultSort }}',
        direction: '{{ $defaultDirection }}',
        filters: {},
        searchTimeout: null,

        init() {
            this.fetchData();
        },

        fetchData(page = 1) {
            this.loading = true;
            this.meta.current_page = page;

            let params = new URLSearchParams({
                page: this.meta.current_page,
                per_page: this.meta.per_page,
                search: this.search,
                sort: this.sort,
                direction: this.direction,
                ...this.filters
            });

            fetch(`${this.endpoint}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(res => {
                this.items = res.data || [];
                if (res.meta) {
                    this.meta = {
                        current_page: res.meta.current_page || 1,
                        last_page: res.meta.last_page || 1,
                        per_page: res.meta.per_page || 10,
                        total: res.meta.total || 0,
                        from: ((res.meta.current_page - 1) * res.meta.per_page) + (this.items.length ? 1 : 0),
                        to: ((res.meta.current_page - 1) * res.meta.per_page) + this.items.length
                    };
                }
            })
            .catch(err => {
                console.error(err);
                this.items = [];
            })
            .finally(() => {
                this.loading = false;
            });
        },

        onSearchInput() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.fetchData(1);
            }, 350);
        },

        sortBy(column) {
            if (this.sort === column) {
                this.direction = this.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort = column;
                this.direction = 'asc';
            }
            this.fetchData(1);
        },

        setFilter(key, value) {
            this.filters[key] = value;
            this.fetchData(1);
        },

        resetFilters() {
            this.search = '';
            this.filters = {};
            this.fetchData(1);
        }
    }"
    class="w-full space-y-4"
    id="{{ $id }}"
>
    <!-- Top Bar: Filter Slot, Search Box & Per Page -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1 max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input 
                type="text" 
                x-model="search" 
                @input="onSearchInput"
                placeholder="Cari data..." 
                class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] text-[var(--color-text-main)] shadow-2xs focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
            />
            <button 
                x-show="search" 
                @click="search = ''; fetchData(1)" 
                type="button" 
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
            >
                &times;
            </button>
        </div>

        <!-- Custom Filters Slot or Right Tools -->
        <div class="flex items-center gap-2">
            @if(isset($filterSlot))
                {{ $filterSlot }}
            @endif

            <!-- Per Page Select -->
            <div class="flex items-center gap-1.5 text-xs text-[var(--color-text-muted)]">
                <span>Baris:</span>
                <select 
                    x-model="meta.per_page" 
                    @change="fetchData(1)" 
                    class="rounded-lg border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] px-2 py-1.5 text-xs text-[var(--color-text-main)] focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Container with Internal Horizontal Scroll on narrow viewports -->
    <div class="w-full overflow-hidden rounded-xl border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] shadow-xs relative">
        <!-- Loading Overlay -->
        <div 
            x-show="loading" 
            x-transition 
            class="absolute inset-0 bg-white/70 backdrop-blur-2xs z-10 flex items-center justify-center"
            style="display: none;"
        >
            <div class="flex items-center gap-2 text-xs font-semibold text-[var(--color-primary)]">
                <svg class="animate-spin w-5 h-5 text-[var(--color-accent)]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memuat data...</span>
            </div>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left text-xs text-[var(--color-text-main)] border-collapse">
                <thead class="bg-[var(--color-surface)]/70 border-b border-[var(--color-surface-border)] text-[var(--color-text-main)] font-semibold uppercase tracking-wider text-[11px]">
                    <tr>
                        {{ $header ?? '' }}
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-border)]">
                    <!-- Template for row rendering will be provided by slot -->
                    {{ $slot }}

                    <!-- Empty State -->
                    <tr x-show="!loading && items.length === 0">
                        <td colspan="100" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-8 h-8 opacity-40 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="font-medium text-xs">Tidak ada data ditemukan</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="px-4 py-3 border-t border-[var(--color-surface-border)] bg-[var(--color-surface)]/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[var(--color-text-muted)]">
            <div>
                Menampilkan <span class="font-bold text-[var(--color-text-main)]" x-text="meta.from"></span> - <span class="font-bold text-[var(--color-text-main)]" x-text="meta.to"></span> dari <span class="font-bold text-[var(--color-text-main)]" x-text="meta.total"></span> data
            </div>

            <div class="flex items-center gap-1.5" x-show="meta.last_page > 1">
                <button 
                    type="button" 
                    @click="fetchData(meta.current_page - 1)" 
                    :disabled="meta.current_page <= 1"
                    class="px-2.5 py-1 rounded border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] hover:bg-[var(--color-surface)] disabled:opacity-40 disabled:cursor-not-allowed text-xs font-medium"
                >
                    &laquo; Sebelumnya
                </button>

                <span class="px-2 font-medium">
                    Halaman <span class="font-bold text-[var(--color-text-main)]" x-text="meta.current_page"></span> dari <span class="font-bold text-[var(--color-text-main)]" x-text="meta.last_page"></span>
                </span>

                <button 
                    type="button" 
                    @click="fetchData(meta.current_page + 1)" 
                    :disabled="meta.current_page >= meta.last_page"
                    class="px-2.5 py-1 rounded border border-[var(--color-surface-border)] bg-[var(--color-surface-card)] hover:bg-[var(--color-surface)] disabled:opacity-40 disabled:cursor-not-allowed text-xs font-medium"
                >
                    Berikutnya &raquo;
                </button>
            </div>
        </div>
    </div>
</div>
