# CU05 Consultar Bitácora

| Campo | Contenido |
|---|---|
| Caso de uso | CU05 Consultar Bitácora |
| Paquete | usuarios_acceso |
| Permiso | `bitacora.consultar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Permitir al administrador revisar las acciones críticas realizadas en el sistema, para fines de seguridad y trazabilidad.

## Descripción
El administrador consulta los registros de la bitácora, que muestran qué usuario realizó cada acción, cuándo y desde qué IP. Puede filtrarlos por usuario, rango de fechas y tipo de acción. La bitácora es solo de lectura: sus registros no se pueden modificar ni eliminar.

## Precondición
El administrador ha iniciado sesión.

## Flujo principal
1. El administrador selecciona "Bitácora" en el panel principal.
2. El sistema muestra los registros, del más reciente al más antiguo, con fecha, hora, usuario, acción, detalle e IP.
3. El administrador ingresa uno o más filtros (usuario, fecha inicial, fecha final, tipo de acción) y presiona "Buscar".
4. El sistema valida que la fecha final no sea anterior a la fecha inicial.
5. El sistema muestra los registros que cumplen los filtros.

## Flujo alternativo
- **4a.** Si la fecha final es anterior a la inicial, el sistema muestra "La fecha final no puede ser anterior a la fecha inicial" y vuelve al paso 3.
- **5a.** Si ningún registro cumple los filtros, el sistema muestra "No se encontraron registros".
- **Limpiar filtros.** El administrador presiona "Limpiar"; el sistema quita los filtros y vuelve a mostrar todos los registros.
- **Ver detalle.** El administrador selecciona un registro; el sistema muestra toda su información.

## Postcondición
El administrador visualiza los registros solicitados. La bitácora no se modifica.
