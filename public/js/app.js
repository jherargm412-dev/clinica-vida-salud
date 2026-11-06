/*
 * JavaScript del sistema (validaciones en el navegador y pequeñas ayudas).
 * La validación definitiva SIEMPRE se hace también en el servidor (controladores).
 */
document.addEventListener('DOMContentLoaded', () => {

    // 1. Validación de formularios con la clase "needs-validation" (estilo Bootstrap)
    document.querySelectorAll('form.needs-validation').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            // Quita espacios al inicio y al final de los campos de texto
            form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach((campo) => {
                campo.value = campo.value.trim();
            });

            if (!form.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
            } else {
                // Evita doble envío deshabilitando el botón
                const boton = form.querySelector('[type="submit"]');
                if (boton) {
                    boton.disabled = true;
                    boton.dataset.textoOriginal = boton.innerHTML;
                    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';
                }
            }
            form.classList.add('was-validated');
        });
    });

    // 2. Botones para mostrar u ocultar una contraseña: data-mostrar-contrasena="idDelCampo"
    document.querySelectorAll('[data-mostrar-contrasena]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const campo = document.getElementById(boton.dataset.mostrarContrasena);
            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            boton.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    });

    // 3. Confirmación antes de acciones importantes: <form data-confirmar="¿Desactivar este registro?">
    document.querySelectorAll('form[data-confirmar]').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            if (!confirm(form.dataset.confirmar)) {
                evento.preventDefault();
                evento.stopImmediatePropagation();
            }
        });
    });

    // 4. Requisitos de contraseña que se marcan en verde mientras se escribe:
    //    <ul data-requisitos-contrasena="idDelCampo"> <li data-regla="largo"><i class="bi bi-circle"></i> ...</li> </ul>
    //    (misma política que app/Modulos/compartido/Contrasena.php)
    const reglasContrasena = {
        largo:     (v) => v.length >= 8,
        mayuscula: (v) => /[A-Z]/.test(v),
        minuscula: (v) => /[a-z]/.test(v),
        numero:    (v) => /\d/.test(v),
        especial:  (v) => /[^A-Za-z0-9]/.test(v),
    };
    document.querySelectorAll('[data-requisitos-contrasena]').forEach((lista) => {
        const campo = document.getElementById(lista.dataset.requisitosContrasena);
        const actualizar = () => {
            lista.querySelectorAll('[data-regla]').forEach((item) => {
                const cumple = reglasContrasena[item.dataset.regla](campo.value);
                item.classList.toggle('text-success', cumple);
                item.querySelector('i').className = cumple ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
        };
        campo.addEventListener('input', actualizar);
        actualizar();
    });

    // 5. Casilla que muestra u oculta varias contraseñas a la vez:
    //    <input type="checkbox" data-mostrar-contrasenas="campo1,campo2,campo3">
    document.querySelectorAll('[data-mostrar-contrasenas]').forEach((casilla) => {
        casilla.addEventListener('change', () => {
            casilla.dataset.mostrarContrasenas.split(',').forEach((id) => {
                const campo = document.getElementById(id.trim());
                if (campo) campo.type = casilla.checked ? 'text' : 'password';
            });
        });
    });

    // 6. Botón modo claro / oscuro: <button data-cambiar-tema><i class="bi"></i></button>
    //    El tema inicial lo pone compartido/_tema.blade.php; aquí solo se cambia y se guarda en este navegador.
    const raiz = document.documentElement;
    const pintarIconos = () => {
        const oscuro = raiz.getAttribute('data-bs-theme') === 'dark';
        document.querySelectorAll('[data-cambiar-tema] i').forEach((icono) => {
            icono.className = icono.className.replace(/bi-(moon-stars|sun)\b/, oscuro ? 'bi-sun' : 'bi-moon-stars');
        });
    };
    document.querySelectorAll('[data-cambiar-tema]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const nuevo = raiz.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            raiz.setAttribute('data-bs-theme', nuevo);
            try { localStorage.setItem('cvs-tema', nuevo); } catch (e) {}
            pintarIconos();
        });
    });
    pintarIconos();

    // 7. Cuenta regresiva (CU21: tiempo para pagar una cita):
    //    <span data-cuenta-regresiva="600" data-recargar>10:00</span>
    //    Muestra mm:ss; al llegar a 0 dice "Vencido" y, si tiene data-recargar, recarga la página
    //    (el servidor libera el horario y muestra el mensaje de tiempo vencido).
    document.querySelectorAll('[data-cuenta-regresiva]').forEach((reloj) => {
        let segundos = parseInt(reloj.dataset.cuentaRegresiva, 10) || 0;
        const pintar = () => {
            if (segundos <= 0) {
                reloj.textContent = 'Vencido';
                reloj.classList.add('text-danger');
                return false;
            }
            const m = String(Math.floor(segundos / 60)).padStart(2, '0');
            const s = String(segundos % 60).padStart(2, '0');
            reloj.textContent = `${m}:${s}`;
            reloj.classList.toggle('text-danger', segundos <= 60);
            return true;
        };
        pintar();
        const intervalo = setInterval(() => {
            segundos--;
            if (!pintar()) {
                clearInterval(intervalo);
                if (reloj.hasAttribute('data-recargar')) {
                    setTimeout(() => window.location.reload(), 1500);
                }
            }
        }, 1000);
    });

    // 8. Semana de horarios (CU11 "Asignar horario"): <form data-semana> con una fila <tr data-dia="1..7"> por día.
    //    - Marcar "Atiende" agrega un tramo si no tiene; desmarcarlo deshabilita sus campos (no se envían).
    //    - "Otro tramo" agrega una fila desde <template id="plantilla-tramo">; "Quitar" la elimina.
    //    - "Copiar el lunes" copia los tramos del lunes a los demás días marcados.
    document.querySelectorAll('form[data-semana]').forEach((form) => {
        const plantilla = form.querySelector('#plantilla-tramo');
        let contador = 1000;   // números para los tramos nuevos (los existentes usan 0, 1, 2...)

        const agregarTramo = (fila, valores = {}) => {
            const html = plantilla.innerHTML
                .replaceAll('__DIA__', fila.dataset.dia)
                .replaceAll('__N__', String(contador++));
            const contenedor = fila.querySelector('[data-tramos]');
            contenedor.insertAdjacentHTML('beforeend', html);
            const tramo = contenedor.lastElementChild;
            Object.entries(valores).forEach(([campo, valor]) => {
                tramo.querySelector(`[data-campo="${campo}"]`).value = valor;
            });
            return tramo;
        };

        const actualizarDia = (fila) => {
            const atiende = fila.querySelector('[data-atiende]').checked;
            fila.querySelectorAll('[data-tramo] input').forEach((campo) => { campo.disabled = !atiende; });
            fila.querySelector('[data-tramos]').classList.toggle('d-none', !atiende);
            fila.querySelector('[data-agregar-tramo]').classList.toggle('d-none', !atiende);
            fila.querySelector('[data-no-atiende]').classList.toggle('d-none', atiende);
        };

        form.querySelectorAll('tr[data-dia]').forEach((fila) => {
            fila.querySelector('[data-atiende]').addEventListener('change', (evento) => {
                if (evento.target.checked && !fila.querySelector('[data-tramo]')) {
                    agregarTramo(fila);
                }
                actualizarDia(fila);
            });
            fila.querySelector('[data-agregar-tramo]').addEventListener('click', () => agregarTramo(fila));
            fila.addEventListener('click', (evento) => {
                if (!evento.target.closest('[data-quitar-tramo]')) return;
                evento.target.closest('[data-tramo]').remove();
                if (!fila.querySelector('[data-tramo]')) {
                    fila.querySelector('[data-atiende]').checked = false;   // sin tramos = no atiende
                    actualizarDia(fila);
                }
            });
            actualizarDia(fila);
        });

        form.querySelector('[data-copiar-lunes]')?.addEventListener('click', (evento) => {
            const lunes = form.querySelector('tr[data-dia="1"]');
            const tramosLunes = [...lunes.querySelectorAll('[data-tramo]')].map((tramo) => ({
                hora_inicio: tramo.querySelector('[data-campo="hora_inicio"]').value,
                hora_fin: tramo.querySelector('[data-campo="hora_fin"]').value,
                duracion_cita: tramo.querySelector('[data-campo="duracion_cita"]').value,
            }));
            const destinos = [...form.querySelectorAll('tr[data-dia]:not([data-dia="1"])')]
                .filter((fila) => fila.querySelector('[data-atiende]').checked);

            if (!lunes.querySelector('[data-atiende]').checked || tramosLunes.length === 0 || destinos.length === 0) {
                alert(evento.currentTarget.dataset.aviso);
                return;
            }
            destinos.forEach((fila) => {
                fila.querySelectorAll('[data-tramo]').forEach((tramo) => tramo.remove());
                tramosLunes.forEach((valores) => agregarTramo(fila, valores));
                actualizarDia(fila);
            });
        });
    });

    // 9. Menú lateral por grupos (compartido/_menu.blade.php): recuerda en este navegador
    //    qué grupos dejó abiertos el usuario. El grupo de la página actual ya viene abierto del servidor.
    const CLAVE_MENU = 'cvs-menu-abiertos';
    let abiertos = [];
    try { abiertos = JSON.parse(localStorage.getItem(CLAVE_MENU)) || []; } catch (e) {}

    document.querySelectorAll('[data-grupo-menu]').forEach((panel) => {
        const grupo = panel.dataset.grupoMenu;
        const boton = document.querySelector(`[data-bs-target="#${panel.id}"]`);

        // Abre (sin animación) los grupos que había dejado abiertos
        if (abiertos.includes(grupo) && !panel.classList.contains('show')) {
            panel.classList.add('show');
            boton?.classList.remove('collapsed');
            boton?.setAttribute('aria-expanded', 'true');
        }

        // Guarda los cambios al abrir o cerrar un grupo
        const guardar = (abierto) => {
            abiertos = abiertos.filter((g) => g !== grupo);
            if (abierto) abiertos.push(grupo);
            try { localStorage.setItem(CLAVE_MENU, JSON.stringify(abiertos)); } catch (e) {}
        };
        panel.addEventListener('shown.bs.collapse', () => guardar(true));
        panel.addEventListener('hidden.bs.collapse', () => guardar(false));
    });
});
