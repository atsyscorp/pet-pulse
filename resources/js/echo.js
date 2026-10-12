import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/*
 * Only the clinical application needs the WebSocket. Public pages (landing,
 * login) must not open a connection: there is no session to authorise the
 * private channel and it would only log errors in the browser console.
 * The app layout declares <meta name="petpulse-realtime">.
 *
 * Reverb speaks the Pusher protocol. Livewire's `echo-private:` listeners use
 * this global instance; private channel auth hits /broadcasting/auth with the
 * session cookie, and routes/channels.php checks the user's clinic.
 */
if (document.querySelector('meta[name="petpulse-realtime"]')) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
