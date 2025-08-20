import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const scheme = (import.meta.env.VITE_REVERB_SCHEME ?? "http").toLowerCase();
const isHttps = scheme === "https";

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? (isHttps ? 443 : 80),
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: isHttps, // http → false, https → true
    encrypted: isHttps,
    enabledTransports: isHttps ? ["ws", "wss"] : ["ws"],
    disableStats: true,
});

console.log("Echo opts:", window.Echo.options);
const DEBUG_WS = (import.meta.env.VITE_DEBUG_WS ?? "false") === "true";

// Logger encapsulado
const dlog = (...a) => DEBUG_WS && console.log(...a);
const dwarn = (...a) => DEBUG_WS && console.warn(...a);
const derr = (...a) => DEBUG_WS && console.error(...a);

dlog("dEcho opts:", window.Echo.options);

// ===== Echo connection timing & RTT metrics (solo si DEBUG_WS) =====
if (DEBUG_WS)
    (function () {
        if (!window.Echo?.connector?.pusher) {
            dwarn("Echo aún no está listo para instrumentar.");
            return;
        }

        const conn = window.Echo.connector.pusher.connection;

        window.wsMetrics = {
            attempts: 0,
            lastStartAt: 0,
            lastConnectedAt: 0,
            lastDurationMs: 0,
            history: [],
        };

        const WARN_THRESHOLD = 3000;
        let warnTimer = null;

        const startAttempt = () => {
            window.wsMetrics.attempts += 1;
            window.wsMetrics.lastStartAt = performance.now();
            clearTimeout(warnTimer);
            warnTimer = setTimeout(() => {
                dwarn(`[WS] sigue "connecting" tras ${WARN_THRESHOLD} ms`);
            }, WARN_THRESHOLD);
        };

        const finishAttempt = () => {
            clearTimeout(warnTimer);
            window.wsMetrics.lastConnectedAt = performance.now();
            window.wsMetrics.lastDurationMs = Math.round(
                window.wsMetrics.lastConnectedAt - window.wsMetrics.lastStartAt
            );
            dlog(`[WS] conectado en ${window.wsMetrics.lastDurationMs} ms`);
            window.wsMetrics.history.push({
                from: "connecting",
                to: "connected",
                ts: new Date().toISOString(),
                durationMs: window.wsMetrics.lastDurationMs,
            });
            // window.Livewire?.dispatch('ws-connected', { ms: window.wsMetrics.lastDurationMs });
        };

        conn.bind("state_change", (s) => {
            dlog("WS state:", s);
            window.wsMetrics.history.push({
                from: s.previous,
                to: s.current,
                ts: new Date().toISOString(),
            });
            if (s.current === "connecting") startAttempt();
            if (s.current === "connected") finishAttempt();
            if (s.current !== "connecting") clearTimeout(warnTimer);
        });

        conn.bind("error", (err) => derr("[WS] error:", err));

        if (conn.state === "connecting") startAttempt();

        // RTT solo loggea si DEBUG_WS
        window.measurePingRTT = async function () {
            return new Promise(async (resolve, reject) => {
                try {
                    const ch = window.Echo.channel("ping");
                    const t0 = performance.now();
                    const handler = (e) => {
                        const dt = Math.round(performance.now() - t0);
                        dlog(`[WS] Ping RTT: ${dt} ms`, e);
                        ch.stopListening(".Ping", handler);
                        resolve(dt);
                    };
                    ch.listen(".Ping", handler);
                    await fetch("/ping");
                    setTimeout(() => {
                        ch.stopListening(".Ping", handler);
                        reject(new Error("Ping timeout"));
                    }, 5000);
                } catch (e) {
                    reject(e);
                }
            });
        };
    })();
