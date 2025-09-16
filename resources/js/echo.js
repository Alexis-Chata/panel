// resources/js/echo.js
import Echo from "laravel-echo";
import Pusher from "pusher-js";
window.Pusher = Pusher;

// Evita doble init
if (window.__ECHO_INITED__) {
    console.warn("Echo ya inicializado — omitiendo este archivo.");
} else {
    window.__ECHO_INITED__ = true;

    const PROFILE = (
        import.meta.env.VITE_ECHO_PROFILE ?? "produccion"
    ).toLowerCase();
    const scheme = (
        import.meta.env.VITE_REVERB_SCHEME ?? "https"
    ).toLowerCase();
    const isHttps = scheme === "https";

    const base = {
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY, // p.ej. localkey
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname, // p.ej. panel.bricenovirtual.com
        forceTLS: isHttps, // si la página es https, que el WS sea wss
        enableStats: false, // evita llamadas /pusher/stats
        // TIP: si quieres bloquear el fallback a long-polling/SockJS:
        // enabledTransports: isHttps ? ['wss'] : ['ws'],
    };

    // Producción: detrás de Nginx en 443, NO tocar wsPath/puertos/cluster
    const produccion = {
        ...base,
        // nada más; los defaults de pusher-js ya usan /app y 443
    };

    // Local: si el navegador conecta DIRECTO al Reverb (sin Nginx)
    // SOLO entonces puedes indicar el puerto. Si usas Nginx en local, no hace falta.
    const local = {
        ...base,
        forceTLS: false,
        wsHost: import.meta.env.VITE_REVERB_HOST || "localhost",
        ...(import.meta.env.VITE_REVERB_PORT
            ? {
                  wsPort: Number(import.meta.env.VITE_REVERB_PORT),
                  wssPort: Number(import.meta.env.VITE_REVERB_PORT),
              }
            : {}),
        // En local suele bastar sólo 'ws'
        enabledTransports: ["ws"],
    };

    const cfg = PROFILE === "local" ? local : produccion;

    window.Echo = new Echo(cfg);
    console.log("Echo profile:", PROFILE, "opts:", window.Echo.options);
}
