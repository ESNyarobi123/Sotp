import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Laravel Echo — connects to Soketi (Pusher-compatible WebSocket server).
 * When BROADCAST_CONNECTION=log (default for dev), Echo won't connect
 * and Livewire falls back to wire:poll gracefully.
 */
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY ?? 'app-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});
