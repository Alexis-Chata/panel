import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

const scheme  = (import.meta.env.VITE_REVERB_SCHEME ?? 'http').toLowerCase();
const isHttps = scheme === 'https';
const PROFILE = import.meta.env.VITE_ECHO_PROFILE ?? 'local';
const PORT    = Number(import.meta.env.VITE_REVERB_PORT ?? (isHttps ? 443 : 80));

const base = {
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPath: '/app',
  wsPort: PORT,
  wssPort: PORT,
  forceTLS: isHttps,
  enabledTransports: isHttps ? ['wss'] : ['ws'],
  cluster: 'mt1',          // requerido por pusher-js v8
  enableStats: false       // reemplaza a disableStats
};

const profiles = {
  local:      { ...base, enabledTransports: isHttps ? ['ws','wss'] : ['ws'] },
  produccion: { ...base }
};

const cfg = profiles[PROFILE] ?? profiles.local;
window.Echo = new Echo(cfg);

console.log('Echo profile:', PROFILE, 'opts:', window.Echo.options);
