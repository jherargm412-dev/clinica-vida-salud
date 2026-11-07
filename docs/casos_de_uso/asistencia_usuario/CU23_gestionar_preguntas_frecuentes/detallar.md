# CU23 Gestionar Preguntas Frecuentes

| Campo | Contenido |
|---|---|
| Caso de uso | CU23 Gestionar Preguntas Frecuentes |
| Paquete | asistencia_usuario |
| Permiso | `faq.gestionar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Mantener actualizadas las preguntas frecuentes con las que responde el asistente virtual (CU24).

## Descripción
El administrador registra, modifica, consulta, desactiva y reactiva las preguntas frecuentes de la clínica. Cada pregunta tiene una categoría (Citas, Pagos, Notificaciones, Mi cuenta o La clínica), el texto de la pregunta, la respuesta y las palabras clave con las que el asistente la reconoce. Solo las preguntas activas se usan en el asistente virtual. Las preguntas no se eliminan, se desactivan. Cada cambio queda registrado en la bitácora.

## Precondición
El administrador ha iniciado sesión y tiene permiso para gestionar las preguntas frecuentes.

## Flujo principal
1. El administrador selecciona "Preguntas frecuentes" en el panel principal.
2. El sistema muestra la lista de preguntas frecuentes (categoría, pregunta, palabras clave y estado), con un filtro por categoría y un buscador por texto.
3. El administrador selecciona "Nueva pregunta".
4. El sistema muestra el formulario con las categorías disponibles.
5. El administrador elige la categoría, escribe la pregunta, la respuesta y las palabras clave separadas por comas, y presiona "Guardar".
6. El sistema valida que los campos obligatorios estén completos, que no superen la longitud permitida y que no exista otra pregunta con el mismo texto.
7. El sistema ordena las palabras clave (en minúsculas y sin repetidas), registra la pregunta como activa y registra la acción en la bitácora.
8. El sistema muestra "Pregunta registrada" y la lista actualizada.

## Flujo alternativo
- **6a.** Si falta la categoría, la pregunta, la respuesta o las palabras clave, el sistema muestra "Complete los campos obligatorios" y vuelve al paso 5.
- **6b.** Si la pregunta supera los 200 caracteres, la respuesta los 1000 o las palabras clave los 300, el sistema muestra "El campo [nombre] no puede superar [N] caracteres" y vuelve al paso 5.
- **6c.** Si ya existe otra pregunta con el mismo texto (sin distinguir mayúsculas ni tildes), el sistema muestra "Ya existe una pregunta frecuente con ese texto" y vuelve al paso 5.
- **6d.** Si las palabras clave no contienen ninguna palabra válida, el sistema muestra "Ingrese al menos una palabra clave" y vuelve al paso 5.
- **Modificar pregunta.** El administrador selecciona una pregunta y cambia sus datos; el sistema aplica las mismas validaciones (6a a 6d, sin compararla consigo misma), actualiza la pregunta, registra la acción en la bitácora y muestra "Pregunta actualizada".
- **Desactivar pregunta.** El administrador selecciona una pregunta activa y confirma; el sistema la marca como inactiva, registra la acción en la bitácora y muestra "Pregunta desactivada". El asistente virtual deja de usarla.
- **Activar pregunta.** El administrador selecciona una pregunta inactiva y confirma; el sistema la marca como activa, registra la acción en la bitácora y muestra "Pregunta activada".
- **Cancelar.** En cualquier momento el administrador presiona "Cancelar" y el sistema vuelve a la lista sin guardar cambios.

## Postcondición
Las preguntas frecuentes quedan registradas o actualizadas con su categoría, respuesta y palabras clave. Solo las activas se usan en el asistente virtual (CU24). Las desactivadas se conservan y cada cambio queda en la bitácora.
