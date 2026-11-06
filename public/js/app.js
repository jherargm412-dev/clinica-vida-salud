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

    // 10. Filtro "Período" de los reportes (CU19): <select data-periodo> muestra solo los campos del período elegido.
    //     Cada campo va en un contenedor <div data-periodo-campo="diario semanal">. Los ocultos se deshabilitan:
    //     así no se envían ni se validan.
    document.querySelectorAll('select[data-periodo]').forEach((select) => {
        const form = select.closest('form');
        const actualizar = () => {
            form.querySelectorAll('[data-periodo-campo]').forEach((caja) => {
                const visible = caja.dataset.periodoCampo.split(' ').includes(select.value);
                caja.classList.toggle('d-none', !visible);
                caja.querySelectorAll('input, select').forEach((campo) => { campo.disabled = !visible; });
            });
        };
        select.addEventListener('change', actualizar);
        actualizar();
    });

    // 11. Rango de fechas (CU19, 6a): la fecha de inicio no puede ser mayor que la de fin.
    //     <input data-fecha-inicio="mensaje"> y <input data-fecha-fin> en el mismo formulario. El servidor lo valida igual.
    document.querySelectorAll('input[data-fecha-inicio]').forEach((inicio) => {
        const fin = inicio.form?.querySelector('input[data-fecha-fin]');
        if (!fin) return;
        const aviso = inicio.parentElement.querySelector('.invalid-feedback');
        const textoOriginal = aviso?.textContent;
        const revisar = () => {
            const mal = inicio.value !== '' && fin.value !== '' && inicio.value > fin.value;
            inicio.setCustomValidity(mal ? inicio.dataset.fechaInicio : '');
            if (aviso) aviso.textContent = mal ? inicio.dataset.fechaInicio : textoOriginal;
        };
        inicio.addEventListener('change', revisar);
        fin.addEventListener('change', revisar);
        revisar();
    });

    // 12. Botones rápidos de fechas (CU19): <button data-periodo-rapido='{"periodo":"diario","fecha":"2026-11-06"}'>
    //     Ponen esos valores en los filtros y generan el reporte (el resto de filtros se mantiene).
    //     Si el usuario cambia a mano un campo de fecha o período, ningún atajo queda resaltado
    //     (el resaltado siempre coincide con lo que dice el formulario).
    document.querySelectorAll('form').forEach((form) => {
        const botones = [...form.querySelectorAll('[data-periodo-rapido]')];
        if (!botones.length) return;
        const camposDeFecha = new Set(botones.flatMap((b) => Object.keys(JSON.parse(b.dataset.periodoRapido))));
        let aplicandoAtajo = false;

        const resaltar = (elegido) => botones.forEach((b) => {
            b.classList.toggle('btn-primary', b === elegido);
            b.classList.toggle('btn-outline-secondary', b !== elegido);
        });

        form.addEventListener('change', (evento) => {
            if (!aplicandoAtajo && camposDeFecha.has(evento.target.name)) resaltar(null);
        });

        botones.forEach((boton) => {
            boton.addEventListener('click', () => {
                aplicandoAtajo = true;
                Object.entries(JSON.parse(boton.dataset.periodoRapido)).forEach(([nombre, valor]) => {
                    const campo = form.querySelector(`[name="${nombre}"]`);
                    if (!campo) return;
                    campo.value = valor;
                    campo.dispatchEvent(new Event('change', { bubbles: true }));   // muestra los campos (10) y revisa fechas (11)
                });
                aplicandoAtajo = false;
                resaltar(boton);
                form.requestSubmit();
            });
        });
    });

    // 13. Gráficos de los reportes (CU19) con Chart.js (cargado por CDN solo en esa pantalla).
    //     <canvas data-grafico="id"> + <script type="application/json" id="id"> con:
    //     { tipo: barras | barras_apiladas | area, etiquetas: [...], series: [{nombre, valores}], moneda: bool }
    //     Colores: paleta validada para daltonismo (modo claro y oscuro). Se vuelven a dibujar al cambiar el tema.
    const lienzos = document.querySelectorAll('canvas[data-grafico]');
    if (lienzos.length && window.Chart) {
        const SERIES = {
            light: ['#2a78d6', '#eb6834', '#1baf7a'],
            dark:  ['#3987e5', '#d95926', '#199e70'],
        };
        const TINTA = {
            light: { texto: '#52514e', ejes: '#898781', grilla: '#e1e0d9' },
            dark:  { texto: '#c3c2b7', ejes: '#898781', grilla: '#2c2c2a' },
        };
        const graficos = [];

        const dibujar = () => {
            const tema = raiz.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
            const tinta = TINTA[tema];
            graficos.splice(0).forEach((g) => g.destroy());

            lienzos.forEach((lienzo) => {
                const datos = JSON.parse(document.getElementById(lienzo.dataset.grafico).textContent);
                // Color del fondo sobre el que está el gráfico: el primer contenedor que no sea transparente
                let superficie = 'transparent';
                for (let el = lienzo.parentElement; el; el = el.parentElement) {
                    const fondo = getComputedStyle(el).backgroundColor;
                    if (fondo && fondo !== 'transparent' && !/rgba\(.*,\s*0\)$/.test(fondo)) { superficie = fondo; break; }
                }
                const apilado = datos.tipo === 'barras_apiladas';
                const area = datos.tipo === 'area';
                const formato = (v) => datos.moneda
                    ? 'Bs ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : Number(v).toLocaleString('en-US');
                const formatoEje = (v) => datos.moneda ? 'Bs ' + Number(v).toLocaleString('en-US') : Number(v).toLocaleString('en-US');

                // En barras apiladas solo el segmento de arriba lleva las esquinas redondeadas
                const esSegmentoSuperior = (ctx) => {
                    for (let d = ctx.chart.data.datasets.length - 1; d >= 0; d--) {
                        if (!ctx.chart.isDatasetVisible(d)) continue;
                        if ((ctx.chart.data.datasets[d].data[ctx.dataIndex] || 0) > 0) return d === ctx.datasetIndex;
                    }
                    return false;
                };

                const datasets = datos.series.map((serie, i) => {
                    const color = SERIES[tema][i % SERIES[tema].length];
                    return area ? {
                        label: serie.nombre, data: serie.valores, borderColor: color, backgroundColor: color + '1a',
                        fill: true, borderWidth: 2, tension: 0, pointRadius: 0, pointHoverRadius: 5,
                        pointHoverBackgroundColor: color, pointHoverBorderColor: superficie, pointHoverBorderWidth: 2,
                    } : {
                        label: serie.nombre, data: serie.valores, backgroundColor: color,
                        maxBarThickness: 24, borderSkipped: 'start',
                        borderRadius: apilado
                            ? (ctx) => (esSegmentoSuperior(ctx) ? { topLeft: 4, topRight: 4 } : 0)
                            : { topLeft: 4, topRight: 4 },
                        // Separación de 2 px del color de la tarjeta entre segmentos apilados
                        borderColor: superficie, borderWidth: apilado ? { top: 2 } : 0,
                    };
                });

                graficos.push(new window.Chart(lienzo, {
                    type: area ? 'line' : 'bar',
                    data: { labels: datos.etiquetas, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            // Leyenda solo si hay más de una serie (con una, el título ya dice qué se grafica)
                            legend: {
                                display: datos.series.length > 1, position: 'top', align: 'start',
                                labels: { color: tinta.texto, usePointStyle: true, pointStyle: 'rectRounded', boxHeight: 8 },
                            },
                            tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${formato(c.parsed.y)}` } },
                        },
                        scales: {
                            x: {
                                stacked: apilado,
                                grid: { display: false },
                                border: { color: tinta.grilla },
                                ticks: { color: tinta.ejes, maxRotation: 0, autoSkip: true, autoSkipPadding: 12 },
                            },
                            y: {
                                stacked: apilado,
                                beginAtZero: true,
                                grace: '10%',   // un poco de aire sobre la barra más alta
                                grid: { color: tinta.grilla },
                                border: { display: false },
                                ticks: { color: tinta.ejes, precision: 0, maxTicksLimit: 5, callback: formatoEje },
                            },
                        },
                    },
                }));
            });
        };

        dibujar();
        // El botón de modo claro/oscuro (sección 6) cambia data-bs-theme: se redibuja con los colores del tema
        new MutationObserver(dibujar).observe(raiz, { attributes: true, attributeFilter: ['data-bs-theme'] });
    }
});
