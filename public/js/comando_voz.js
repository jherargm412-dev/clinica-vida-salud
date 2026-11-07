/*
 * CU25 Navegar por comando de voz: el botón de micrófono de la barra superior.
 *
 * Usa el reconocimiento de voz que trae el navegador (Web Speech API): convierte la voz en texto
 * y lo envía al servidor con un formulario normal (POST /comando-voz). El servidor decide a qué
 * pantalla ir (InterpreteComandoVoz). No hay API ni JSON.
 *
 * En la plantilla (compartido/app.blade.php):
 *   <button data-comando-voz class="d-none">...</button>      -> el botón (oculto hasta saber si hay soporte)
 *   <form id="form-comando-voz"><input name="texto"></form>   -> formulario que se envía
 */
document.addEventListener('DOMContentLoaded', () => {
    const boton = document.querySelector('[data-comando-voz]');
    const formulario = document.getElementById('form-comando-voz');
    if (!boton || !formulario) return;

    // Flujo 1a: si el navegador no tiene reconocimiento de voz (por ejemplo Firefox), el botón no se muestra
    const Reconocimiento = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Reconocimiento) return;
    boton.classList.remove('d-none');

    const icono = boton.querySelector('i');
    let reconocedor = null;
    let escuchando = false;
    let idioma = 'es-BO';   // español de Bolivia; si el navegador no lo tiene, se usa español de España
    let reintentar = false; // true = volver a escuchar con otro idioma al terminar el intento actual

    // Aviso flotante abajo a la derecha ("Escuchando…" o un error)
    const aviso = document.createElement('div');
    aviso.className = 'position-fixed bottom-0 end-0 m-3 d-none';
    aviso.style.zIndex = 1080;
    aviso.setAttribute('role', 'status');
    document.body.appendChild(aviso);

    function mostrarAviso(texto, tipo) {
        aviso.innerHTML = '';
        const caja = document.createElement('div');
        caja.className = `alert alert-${tipo} shadow mb-0 py-2`;
        caja.textContent = texto;   // textContent: nunca interpreta HTML
        aviso.appendChild(caja);
        aviso.classList.remove('d-none');
    }

    function ocultarAviso() {
        aviso.classList.add('d-none');
    }

    function marcarEscuchando(activo) {
        escuchando = activo;
        boton.classList.toggle('text-warning', activo);
        icono.className = activo ? 'bi bi-mic-fill fs-5' : 'bi bi-mic fs-5';
        boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
    }

    // Escucha continua: el navegador NO corta en la primera pausa entre palabras.
    // El comando se envía cuando el usuario deja de hablar SILENCIO_MS, o como máximo a los MAXIMO_MS.
    const SILENCIO_MS = 2000;
    const MAXIMO_MS = 15000;
    let dicho = '';              // lo que se entendió hasta ahora
    let terminado = false;       // true si ya se mostró un error o el usuario canceló (no enviar)
    let temporizadorSilencio = null;
    let temporizadorMaximo = null;

    function limpiarTemporizadores() {
        clearTimeout(temporizadorSilencio);
        clearTimeout(temporizadorMaximo);
    }

    // Pasos 3-4: envía el texto al servidor (que decide a qué pantalla ir)
    function enviar(texto) {
        mostrarAviso(`Comando: «${texto}»`, 'primary');
        formulario.querySelector('input[name="texto"]').value = texto;
        formulario.dataset.enviado = '1';   // el aviso queda visible mientras carga la otra pantalla
        formulario.submit();
    }

    function empezar() {
        dicho = '';
        terminado = false;
        reconocedor = new Reconocimiento();
        reconocedor.lang = idioma;
        reconocedor.continuous = true;        // sigue escuchando aunque haya pausas
        reconocedor.interimResults = true;    // va mostrando lo que entiende mientras se habla
        reconocedor.maxAlternatives = 1;

        // Paso 2: escuchando
        reconocedor.onstart = () => {
            marcarEscuchando(true);
            mostrarAviso('Escuchando… diga un comando. Se envía cuando deje de hablar. Presione el micrófono otra vez para cancelar.', 'info');
            temporizadorMaximo = setTimeout(() => reconocedor.stop(), MAXIMO_MS);
        };

        // Paso 3: cada vez que entiende algo, arma el texto completo y vuelve a esperar el silencio
        reconocedor.onresult = (evento) => {
            const partes = Array.from(evento.results, (resultado) => resultado[0].transcript);
            dicho = partes.join(' ').replace(/\s+/g, ' ').trim();
            if (dicho) {
                mostrarAviso(`Escuchando… «${dicho}»`, 'info');
            }
            clearTimeout(temporizadorSilencio);
            temporizadorSilencio = setTimeout(() => reconocedor.stop(), SILENCIO_MS);
        };

        reconocedor.onerror = (evento) => {
            switch (evento.error) {
                case 'not-allowed':
                case 'service-not-allowed':
                    // Flujo 2a
                    mostrarAviso('Permita el uso del micrófono en su navegador para usar comandos de voz', 'warning');
                    break;
                case 'no-speech':
                    // Flujo 3a
                    mostrarAviso('No se escuchó ningún comando. Intente de nuevo', 'warning');
                    break;
                case 'language-not-supported':
                    // Se reintenta una sola vez con español de España (al terminar este intento, en onend)
                    if (idioma !== 'es-ES') {
                        idioma = 'es-ES';
                        reintentar = true;
                        terminado = true;
                        return;
                    }
                    mostrarAviso('Su navegador no reconoce voz en español', 'warning');
                    break;
                case 'aborted':
                    // El usuario canceló (flujo "Cancelar"): no se muestra nada
                    ocultarAviso();
                    break;
                default:
                    mostrarAviso('No se pudo usar el micrófono. Revise su conexión e intente de nuevo', 'warning');
            }
            terminado = true;   // ya se mostró un mensaje (o se canceló): no se envía nada
        };

        // Dejó de escuchar (silencio, tiempo máximo, error o cancelación)
        reconocedor.onend = () => {
            limpiarTemporizadores();
            marcarEscuchando(false);
            if (reintentar) {
                reintentar = false;
                empezar();
                return;
            }
            if (!terminado && dicho) {
                enviar(dicho);
                return;
            }
            // Terminó sin entender nada (flujo 3a)
            if (!terminado) {
                mostrarAviso('No se escuchó ningún comando. Intente de nuevo', 'warning');
            }
            setTimeout(() => { if (!escuchando && !formulario.dataset.enviado) ocultarAviso(); }, 4000);
        };

        reconocedor.start();
    }

    boton.addEventListener('click', () => {
        // Flujo "Cancelar": si ya está escuchando, deja de escuchar sin enviar nada
        if (escuchando && reconocedor) {
            terminado = true;
            limpiarTemporizadores();
            reconocedor.abort();
            ocultarAviso();
            return;
        }
        empezar();
    });
});
