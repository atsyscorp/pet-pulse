// Public-site behavior: theme toggle (light / dark / system) and the phone menu.
// No framework dependency: these pages load before any Livewire component does.

// Only the public layout opts in; the clinical app keeps its own chrome.
const enabled = document.body.classList.contains('pp-body');

const STORAGE_KEY = 'petpulse-theme';
const root = document.documentElement;
const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');

function readPreference() {
    try {
        return localStorage.getItem(STORAGE_KEY) || 'light';
    } catch {
        return 'light';
    }
}

function apply(preference) {
    const dark = preference === 'dark' || (preference === 'system' && colorScheme.matches);
    root.dataset.theme = dark ? 'dark' : 'light';
    root.dataset.themePref = preference;

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.content = dark ? '#120c36' : '#f1f5f9';

    document.querySelectorAll('[data-theme-set]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.themeSet === preference));
    });
}

document.addEventListener('click', (event) => {
    if (!enabled) return;

    const choice = event.target.closest('[data-theme-set]');
    if (choice) {
        const preference = choice.dataset.themeSet;
        try {
            localStorage.setItem(STORAGE_KEY, preference);
        } catch {
            // Private mode: the choice lasts for this page only.
        }
        apply(preference);
        return;
    }

    const toggle = event.target.closest('[data-menu-toggle]');
    const menu = document.getElementById('menu-movil');
    if (toggle && menu) {
        const open = menu.hasAttribute('hidden');
        menu.toggleAttribute('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        return;
    }

    if (menu && event.target.closest('#menu-movil a')) {
        menu.setAttribute('hidden', '');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', 'false');
    }
});

colorScheme.addEventListener('change', () => {
    if (enabled && readPreference() === 'system') apply('system');
});

if (enabled) apply(readPreference());
