<div 
    x-data="{
        notices: [],
        add(notice) {
            notice.id = Date.now();
            this.notices.push(notice);
            setTimeout(() => {
                this.remove(notice.id);
            }, 5000);
        },
        remove(id) {
            this.notices = this.notices.filter(n => n.id !== id);
        }
    }"
    x-init="
        @if(session('success'))
            add({ type: 'success', text: '{{ addslashes(session('success')) }}' });
        @endif
        @if(session('error'))
            add({ type: 'error', text: '{{ addslashes(session('error')) }}' });
        @endif
        @if(session('warning'))
            add({ type: 'warning', text: '{{ addslashes(session('warning')) }}' });
        @endif
        @if(session('info'))
            add({ type: 'info', text: '{{ addslashes(session('info')) }}' });
        @endif
    "
    class="fixed top-20 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
>
    <template x-for="notice in notices" :key="notice.id">
        <div 
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto rounded-xl border p-4 shadow-lg flex items-start gap-3 bg-[var(--color-surface-card)]"
            :class="{
                'border-emerald-300 text-emerald-900 bg-emerald-50': notice.type === 'success',
                'border-rose-300 text-rose-900 bg-rose-50': notice.type === 'error',
                'border-amber-300 text-amber-900 bg-amber-50': notice.type === 'warning',
                'border-sky-300 text-sky-900 bg-sky-50': notice.type === 'info'
            }"
        >
            <div class="shrink-0 mt-0.5">
                <template x-if="notice.type === 'success'">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                <template x-if="notice.type === 'error'">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                <template x-if="notice.type === 'warning'">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </template>
                <template x-if="notice.type === 'info'">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
            </div>
            <div class="flex-1 text-xs font-medium leading-relaxed" x-text="notice.text"></div>
            <button 
                type="button" 
                @click="remove(notice.id)" 
                class="shrink-0 p-1 text-slate-400 hover:text-slate-600 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>
