import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Laravel Echo — connects to Soketi (Pusher-compatible WebSocket server).
 * Only initializes when a valid Pusher key is configured via VITE_PUSHER_APP_KEY.
 * When not configured, Livewire falls back to wire:poll gracefully.
 */
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey && pusherKey !== 'app-key') {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        wsHost: import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
        wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
        wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });
}
