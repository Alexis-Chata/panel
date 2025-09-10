import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const scheme = (import.meta.env.VITE_REVERB_SCHEME ?? "http").toLowerCase();
const isHttps = scheme === "https";

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: isHttps, // http → false, https → true
    encrypted: isHttps,
    enabledTransports: isHttps ? ["ws", "wss"] : ["ws"],
    disableStats: true,
});

console.log("Echo opts:", window.Echo.options);
