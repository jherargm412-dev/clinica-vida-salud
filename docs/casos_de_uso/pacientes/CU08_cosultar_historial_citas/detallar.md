# CU08 Consultar Historial de Citas

| Campo | Contenido |
|---|---|
| Caso de uso | CU08 Consultar Historial de Citas |
| Paquete | pacientes |
| Permiso | `historial.consultar` |
| Actores | Recepcionista y paciente con ficha asociada |

## Propósito
Consultar las citas pasadas y actuales de un paciente, incluyendo las canceladas.

## Flujo principal
1. El usuario abre "Historial de citas".
2. Si tiene una ficha de paciente asociada, el sistema muestra únicamente su propio historial.
3. En caso contrario, el sistema permite buscar pacientes por CI, nombre o apellido.
4. El usuario selecciona al paciente y el sistema presenta sus citas ordenadas de la más reciente a la más antigua.
5. El usuario puede filtrar el historial por estado y volver a quitar el filtro.
6. Antes de consultar, el sistema libera las reservas de pago vencidas para que las citas aparezcan con su estado actualizado.

## Reglas y alternativas
- La búsqueda administrativa incluye pacientes activos e inactivos.
- La paginación conserva el filtro de estado.
- Un usuario asociado a un paciente no puede buscar ni abrir historiales de otros pacientes.
- Si no hay resultados, se informa que el paciente aún no tiene citas o que no hay citas con el estado seleccionado.
- Una cuenta sin ficha de paciente no puede acceder a la ruta de historial propio.

## Postcondición
El usuario visualiza solo el historial que le permite su permiso y asociación de paciente; no se modifican las citas salvo la liberación de reservas vencidas.
