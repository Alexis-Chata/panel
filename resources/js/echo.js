import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const scheme = (import.meta.env.VITE_REVERB_SCHEME ?? "http").toLowerCase();
const isHttps = scheme === "https";

const PROFILE = import.meta.env.VITE_ECHO_PROFILE ?? 'local';

const profiles = {
  local: {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: isHttps, // http → false, https → true
    encrypted: isHttps,
    enabledTransports: isHttps ? ["ws", "wss"] : ["ws"],
    disableStats: true,
  },
  produccion: {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: isHttps, // http → false, https → true
    encrypted: isHttps,
    enabledTransports: isHttps ? ["wss"] : ["ws"],
    disableStats: true,
  },
};

const cfg = profiles[PROFILE] ?? profiles.local;
window.Echo = new Echo(cfg);

console.log('Echo profile:', PROFILE, 'opts:', window.Echo.options);
