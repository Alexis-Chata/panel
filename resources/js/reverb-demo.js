// resources/js/reverb-demo.js
// Este código se ejecuta DESPUÉS de crear window.Echo

// Canal PÚBLICO llamado "debug"
if (window.Echo) {
  window.Echo.channel('debug')
    // Si en tu evento usaste ->broadcastAs('DebugPing'), escucha con punto:
    .listen('.DebugPing', (e) => {
      console.log('RX DebugPing:', e);
    });

  // Ejemplos extra (coméntalos si no los usas):
  // Canal PRIVADO "orders.123"
  // window.Echo.private('orders.123')
  //   .listen('OrderShipped', (e) => console.log('OrderShipped', e));

  // Canal PRESENCE "chat.general"
  // window.Echo.join('chat.general')
  //   .here(users => console.log('Conectados:', users))
  //   .joining(u => console.log('Entra:', u))
  //   .leaving(u => console.log('Sale:', u))
  //   .listenForWhisper('typing', p => console.log('typing', p));
} else {
  console.warn('Echo aún no está listo');
}
