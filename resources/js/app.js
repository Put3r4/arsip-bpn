import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        current: document.documentElement.getAttribute('data-theme') || localStorage.getItem('theme') || 'samudra',
        init() {
            try {
                const saved = localStorage.getItem('theme');
                if (saved && ['samudra', 'marun', 'senja'].includes(saved)) {
                    this.current = saved;
                    document.documentElement.setAttribute('data-theme', saved);
                }
            } catch (e) {
                // Silently fallback if localStorage is disabled
            }
        },
        set(theme) {
            if (!['samudra', 'marun', 'senja'].includes(theme)) return;
            this.current = theme;
            document.documentElement.setAttribute('data-theme', theme);
            try {
                localStorage.setItem('theme', theme);
            } catch (e) {
                // Silently ignore
            }
            
            // Sync with server if logged in; silently catch all errors (e.g. 404)
            if (window.APP_USER_AUTHENTICATED) {
                try {
                    fetch('/profile/theme', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ theme })
                    })
                    .then(() => {})
                    .catch(() => {
                        // Silently ignore network or 404 errors until Phase 2
                    });
                } catch (e) {
                    // Silently ignore
                }
            }
        }
    });
});

Alpine.start();
