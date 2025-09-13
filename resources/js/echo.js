import Echo from 'laravel-echo';

// Para Reverb no necesitas pusher-js; si lo mantienes, no afecta.
import Pusher from 'pusher-js';
window.Pusher = Pusher;
Pusher.logToConsole = false;

const scheme = (import.meta.env.VITE_REVERB_SCHEME ?? "http").toLowerCase();
const isHttps = scheme === "https";
const port = Number(import.meta.env.VITE_REVERB_PORT ?? (isHttps ? 443 : 80));

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  wsPath: '/app',
  forceTLS: isHttps,
  enabledTransports: isHttps ? ["wss"] : ["ws"],
  enableStats: false,
});

console.log("Echo opts:", window.Echo.options);
