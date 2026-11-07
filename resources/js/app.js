import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    // Global toast dispatcher
    Alpine.store('toast', {
        notify(type, text) {
            window.dispatchEvent(new CustomEvent('toast-notify', { detail: { type, text } }));
        }
    });

    Alpine.store('theme', {
        current: document.documentElement.getAttribute('data-theme') || 'samudra',
        init() {
            if (window.APP_USER_AUTHENTICATED) {
                // Database theme is single source of truth for authenticated user;
                // overwrite any different localStorage value.
                const serverTheme = document.documentElement.getAttribute('data-theme') || 'samudra';
                this.current = serverTheme;
                try {
                    localStorage.setItem('theme', serverTheme);
                } catch (e) {}
            } else {
                // Guest mode: load preview from localStorage if present
                try {
                    const saved = localStorage.getItem('theme');
                    if (saved && ['samudra', 'marun', 'senja'].includes(saved)) {
                        this.current = saved;
                        document.documentElement.setAttribute('data-theme', saved);
                    }
                } catch (e) {}
            }
        },
        async set(theme) {
            if (!['samudra', 'marun', 'senja'].includes(theme)) return;

            const previousTheme = this.current;
            this.current = theme;
            document.documentElement.setAttribute('data-theme', theme);
            try {
                localStorage.setItem('theme', theme);
            } catch (e) {}

            // For authenticated user, sync with server; notify on non-2xx response
            if (window.APP_USER_AUTHENTICATED) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch('/profile/theme', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ theme })
                    });

                    if (!response.ok) {
                        // Revert on failure
                        this.current = previousTheme;
                        document.documentElement.setAttribute('data-theme', previousTheme);
                        try {
                            localStorage.setItem('theme', previousTheme);
                        } catch (e) {}

                        let errorMsg = 'Gagal menyimpan tema ke server.';
                        try {
                            const data = await response.json();
                            if (data.message) {
                                errorMsg = data.message;
                            }
                        } catch (_) {}

                        Alpine.store('toast').notify('error', errorMsg);
                    }
                } catch (err) {
                    // Network / fetch error
                    this.current = previousTheme;
                    document.documentElement.setAttribute('data-theme', previousTheme);
                    try {
                        localStorage.setItem('theme', previousTheme);
                    } catch (e) {}

                    Alpine.store('toast').notify('error', 'Koneksi gagal. Perubahan tema tidak dapat disimpan.');
                }
            }
        }
    });
});

Alpine.start();
