/*
 * Service worker de Clínica Vida Salud (RF30/RF31).
 * El navegador lo deja instalado al activar las notificaciones en "Mi perfil" y lo despierta
 * cada vez que el servidor envía un aviso push, aunque la página esté cerrada.
 * El mensaje lo arma App\Modulos\pagos\service\Notificador: { titulo, texto, url, etiqueta }.
 */

// Llega un aviso: se muestra como notificación del sistema
self.addEventListener('push', (evento) => {
    let datos = {};
    try {
        datos = evento.data ? evento.data.json() : {};
    } catch (e) {
        datos = { texto: evento.data ? evento.data.text() : '' };
    }

    evento.waitUntil(self.registration.showNotification(datos.titulo || 'Clínica Vida Salud', {
        body: datos.texto || '',
        icon: '/img/icono-192.png',
        badge: '/img/icono-192.png',
        tag: datos.etiqueta || undefined,     // única por aviso: cada uno sale como notificación propia
        data: { url: datos.url || '/' },
    }));
});

// El paciente pulsa el aviso: se abre (o se enfoca) "Mis citas"
self.addEventListener('notificationclick', (evento) => {
    evento.notification.close();
    const url = evento.notification.data && evento.notification.data.url ? evento.notification.data.url : '/';

    evento.waitUntil((async () => {
        const ventanas = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const ventana of ventanas) {
            if (ventana.url === url && 'focus' in ventana) return ventana.focus();
        }
        return self.clients.openWindow(url);
    })());
});
