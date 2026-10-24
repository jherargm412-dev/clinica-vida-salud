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
});
