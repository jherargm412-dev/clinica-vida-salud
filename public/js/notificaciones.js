/*
 * RF30 (CU04 Configurar perfil): activar o desactivar las notificaciones push en ESTE navegador.
 * Se carga solo en "Mi perfil" de un paciente (resources/views/usuarios_acceso/configurar_perfil/ver.blade.php).
 *
 * Pasos para activar:
 *   1. Registrar el service worker (public/sw.js): el archivo que muestra los avisos aunque la página esté cerrada.
 *   2. Pedir permiso al usuario ("¿Permitir notificaciones?").
 *   3. Suscribirse al servicio push del navegador con nuestra clave pública VAPID.
 *   4. Llenar el formulario oculto con los datos de la suscripción y enviarlo (POST normal, sin API ni JSON).
 */
document.addEventListener('DOMContentLoaded', () => {
    const tarjeta = document.querySelector('[data-push]');
    if (!tarjeta) return;

    const clavePublica = tarjeta.dataset.clavePublica;
    const estado = tarjeta.querySelector('[data-push-estado]');
    const mensaje = tarjeta.querySelector('[data-push-mensaje]');
    const botonActivar = tarjeta.querySelector('[data-push-activar]');
    const botonDesactivar = tarjeta.querySelector('[data-push-desactivar]');
    const formulario = tarjeta.querySelector('[data-push-form]');

    // ---------- Ayudantes ----------
    const mostrarEstado = (texto, color) => {
        estado.textContent = texto;
        estado.className = 'badge text-bg-' + color;
    };
    const mostrarMensaje = (texto) => {
        mensaje.textContent = texto;
        mensaje.classList.toggle('d-none', !texto);
    };
    const mostrarBotones = (activar, desactivar) => {
        botonActivar.classList.toggle('d-none', !activar);
        botonDesactivar.classList.toggle('d-none', !desactivar);
    };

    // La clave VAPID viene en base64 "URL-safe"; el navegador la pide como bytes
    const base64UrlABytes = (texto) => {
        const relleno = '='.repeat((4 - (texto.length % 4)) % 4);
        const base64 = (texto + relleno).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
    };

    // ¿La suscripción del navegador se hizo con NUESTRA clave? (si la clave cambió, hay que renovarla)
    const usaNuestraClave = (suscripcion) => {
        const clave = suscripcion.options && suscripcion.options.applicationServerKey;
        if (!clave) return true;
        const actual = new Uint8Array(clave);
        const nuestra = base64UrlABytes(clavePublica);
        return actual.length === nuestra.length && actual.every((byte, i) => byte === nuestra[i]);
    };

    // Texto que se guarda para reconocer el dispositivo en la lista, p. ej. "Chrome en Windows"
    const describirDispositivo = () => {
        const ua = navigator.userAgent;
        const navegador = /Edg\//.test(ua) ? 'Edge' : /OPR\//.test(ua) ? 'Opera' : /Firefox\//.test(ua) ? 'Firefox'
            : /Chrome\//.test(ua) ? 'Chrome' : /Safari\//.test(ua) ? 'Safari' : 'Navegador';
        const sistema = /Android/.test(ua) ? 'Android' : /iPhone|iPad/.test(ua) ? 'iPhone/iPad'
            : /Windows/.test(ua) ? 'Windows' : /Mac OS X/.test(ua) ? 'Mac' : /Linux/.test(ua) ? 'Linux' : 'otro sistema';
        return navegador + ' en ' + sistema;
    };

    // Elemento de la lista del servidor que corresponde a este navegador (si está activo)
    const elementoDeEsteDispositivo = (endpoint) =>
        Array.from(tarjeta.querySelectorAll('[data-push-dispositivo]'))
            .find((li) => li.dataset.pushDispositivo === endpoint);

    // ---------- Comprobaciones iniciales ----------
    if (!clavePublica) {
        mostrarEstado('No disponible', 'secondary');
        mostrarMensaje('Las notificaciones push no están configuradas en el sistema. Los avisos le llegarán por correo.');
        return;
    }
    if (!window.isSecureContext) {
        mostrarEstado('No disponible', 'secondary');
        mostrarMensaje('Las notificaciones solo funcionan con una conexión segura (https).');
        return;
    }
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        mostrarEstado('No compatible', 'secondary');
        mostrarMensaje('Este navegador no admite notificaciones. En iPhone o iPad: toque Compartir → "Agregar a inicio" '
            + 'y abra la clínica desde ese ícono.');
        return;
    }

    let registro = null;

    const actualizar = async () => {
        if (Notification.permission === 'denied') {
            mostrarEstado('Bloqueadas', 'danger');
            mostrarMensaje('Bloqueó las notificaciones para este sitio. Permítalas desde el candado junto a la dirección '
                + 'del navegador y recargue la página.');
            mostrarBotones(false, false);
            return;
        }

        const suscripcion = await registro.pushManager.getSubscription();
        const elemento = suscripcion ? elementoDeEsteDispositivo(suscripcion.endpoint) : null;

        if (elemento) {
            elemento.querySelector('[data-push-este]').classList.remove('d-none');
            mostrarEstado('Activadas', 'success');
            mostrarBotones(false, true);
        } else {
            mostrarEstado('Desactivadas', 'secondary');
            mostrarBotones(true, false);
        }
        mostrarMensaje('');
    };

    // 1. Registrar el service worker (si ya estaba registrado, el navegador reutiliza el mismo)
    navigator.serviceWorker.register(tarjeta.dataset.serviceWorker || '/sw.js')
        .then(() => navigator.serviceWorker.ready)
        .then((listo) => { registro = listo; return actualizar(); })
        .catch(() => {
            mostrarEstado('No disponible', 'secondary');
            mostrarMensaje('No se pudo preparar el navegador para recibir notificaciones.');
        });

    // ---------- Activar ----------
    botonActivar.addEventListener('click', async () => {
        botonActivar.disabled = true;
        try {
            // 2. Permiso del usuario
            const permiso = await Notification.requestPermission();
            if (permiso !== 'granted') {
                await actualizar();
                if (permiso === 'default') mostrarMensaje('No se activaron: debe pulsar "Permitir" en el aviso del navegador.');
                return;
            }

            // 3. Suscripción al servicio push del navegador (se renueva si se hizo con otra clave)
            let suscripcion = await registro.pushManager.getSubscription();
            if (suscripcion && !usaNuestraClave(suscripcion)) {
                await suscripcion.unsubscribe();
                suscripcion = null;
            }
            if (!suscripcion) {
                suscripcion = await registro.pushManager.subscribe({
                    userVisibleOnly: true,                              // cada push muestra un aviso visible
                    applicationServerKey: base64UrlABytes(clavePublica),
                });
            }

            // 4. Enviar los datos al servidor con el formulario oculto
            const datos = suscripcion.toJSON();
            formulario.elements.namedItem('endpoint').value = datos.endpoint;
            formulario.elements.namedItem('p256dh').value = datos.keys.p256dh;
            formulario.elements.namedItem('auth').value = datos.keys.auth;
            formulario.elements.namedItem('dispositivo').value = describirDispositivo();
            formulario.submit();
        } catch (error) {
            mostrarMensaje('No se pudieron activar las notificaciones en este navegador. Intente nuevamente.');
        } finally {
            botonActivar.disabled = false;
        }
    });

    // ---------- Desactivar este dispositivo ----------
    botonDesactivar.addEventListener('click', async () => {
        const suscripcion = await registro.pushManager.getSubscription();
        const elemento = suscripcion ? elementoDeEsteDispositivo(suscripcion.endpoint) : null;
        if (!elemento || !confirm('¿Desactivar las notificaciones en este dispositivo?')) return;

        await suscripcion.unsubscribe();             // el navegador deja de aceptar avisos
        elemento.querySelector('form').submit();     // y el servidor la marca como inactiva
    });
});
