# CU25 Navegar por Comando de Voz

| Campo | Contenido |
|---|---|
| Caso de uso | CU25 Navegar por Comando de Voz |
| Paquete | asistencia_usuario |
| Permiso | `voz.usar` |
| Actores | Administrador, Recepcionista, Médico, Paciente |
| Actor iniciador | Cualquier usuario con sesión iniciada |

## Propósito
Permitir al usuario buscar información y abrir las pantallas del sistema diciendo un comando de voz.

## Descripción
El usuario presiona el botón de micrófono y dice un comando. El navegador convierte la voz en texto y el sistema lo interpreta con reglas: identifica qué se quiere hacer y el dato que acompaña al comando (un nombre, un CI, una especialidad o una fecha como "hoy" o "mañana"), y lleva al usuario a la pantalla correspondiente con la búsqueda ya aplicada. Los comandos de voz solo buscan y navegan; nunca registran, modifican, cancelan, cobran ni desactivan nada. Antes de redirigir, el sistema verifica que el rol del usuario tenga permiso para la pantalla destino. La función está disponible en los navegadores que tienen reconocimiento de voz (Chrome, Edge y Safari); en los demás el botón no se muestra. La navegación por voz no se registra en la bitácora.

Comandos disponibles:

| Comando | Ejemplo | Lleva a | Roles |
|---|---|---|---|
| inicio | "ir al inicio" | Panel principal | Todos |
| abrir / ir a [módulo] | "abrir pacientes", "ir a reportes" | La opción del menú con ese nombre | Todos (según sus permisos) |
| ayuda | "qué puedo decir" | Lista de comandos disponibles para su rol | Todos |
| buscar paciente [nombre o CI] | "buscar paciente Pérez" | Pacientes, filtrado | Recepcionista |
| buscar médico [nombre o especialidad] | "buscar médico cardiología" | Médicos, filtrado | Administrador |
| historial de [paciente] | "historial de Pérez" | Historial de citas, filtrado | Recepcionista |
| registrar cita / nueva cita | "nueva cita" | Registrar cita | Recepcionista, Paciente |
| disponibilidad de [especialidad] | "disponibilidad de pediatría" | Disponibilidad de esa especialidad | Recepcionista, Paciente |
| citas de hoy / citas de mañana | "citas de mañana" | Controlar estado de citas, filtrado por fecha | Recepcionista |
| mi agenda / mi agenda de mañana | "mi agenda de mañana" | Mi agenda, en esa fecha | Médico |

## Precondición
El usuario ha iniciado sesión, tiene permiso para usar comandos de voz, su navegador tiene reconocimiento de voz y su equipo tiene micrófono.

## Flujo principal
1. El usuario presiona el botón de micrófono en la barra superior.
2. El navegador solicita permiso para usar el micrófono (solo la primera vez) y el sistema muestra "Escuchando…".
3. El usuario dice un comando.
4. El navegador convierte la voz en texto y el sistema muestra lo que va entendiendo. Cuando el usuario deja de hablar (2 segundos de silencio, o como máximo 15 segundos), el sistema envía el texto al servidor.
5. El sistema prepara el texto: lo pasa a minúsculas, le quita tildes y las palabras de relleno (por favor, el, la, al, del, doctor, doctora, etc.).
6. El sistema identifica el comando y su dato (nombre, CI, especialidad o fecha).
7. El sistema verifica que el usuario tenga permiso para la pantalla destino.
8. El sistema lleva al usuario a la pantalla destino, con la búsqueda aplicada si el comando incluía un dato, y muestra el comando reconocido (por ejemplo, "Comando: buscar paciente pérez").

## Flujo alternativo
- **1a.** Si el navegador no tiene reconocimiento de voz, el sistema no muestra el botón de micrófono.
- **2a.** Si el usuario no permite el uso del micrófono, el sistema muestra "Permita el uso del micrófono en su navegador para usar comandos de voz".
- **3a.** Si no se escucha ningún comando, el sistema muestra "No se escuchó ningún comando. Intente de nuevo".
- **6a.** Si el texto no corresponde a ningún comando, el sistema muestra "No entendí el comando «[texto]». Diga «ayuda» para ver los comandos disponibles" y el usuario permanece en la pantalla en la que estaba.
- **6b.** Si el comando es "ayuda", el sistema muestra la lista de comandos disponibles para el rol del usuario. La misma lista se abre desde la opción "Comandos de voz" del menú.
- **6c.** Si el comando de disponibilidad nombra una especialidad que no existe o no está activa, el sistema muestra "No encontré la especialidad «[nombre]»".
- **6d.** Si un comando de búsqueda no incluye el dato (por ejemplo, solo "buscar paciente"), el sistema abre la pantalla sin filtro.
- **7a.** Si el usuario no tiene permiso para la pantalla destino, el sistema muestra "No tiene acceso a «[pantalla]»" y el usuario permanece en la pantalla en la que estaba.
- **Cancelar.** Si el usuario vuelve a presionar el botón de micrófono mientras se escucha, el sistema deja de escuchar sin enviar nada.

## Postcondición
El usuario queda en la pantalla que pidió, con la búsqueda aplicada, o recibe un mensaje que explica por qué no se ejecutó el comando. No se modifica la base de datos.
