# CU24 Consultar Asistente Virtual

| Campo | Contenido |
|---|---|
| Caso de uso | CU24 Consultar Asistente Virtual |
| Paquete | asistencia_usuario |
| Permiso | `asistente.consultar` |
| Actores | Paciente |
| Actor iniciador | Paciente |

## Propósito
Responder automáticamente las dudas frecuentes del paciente sobre citas, pagos, notificaciones, su cuenta de usuario y la clínica.

## Descripción
El asistente virtual es un chatbot basado en reglas: no usa inteligencia artificial. El paciente escribe una pregunta o elige una categoría, y el sistema busca entre las preguntas frecuentes activas (CU23) la que más coincide con lo escrito, comparando las palabras sin distinguir mayúsculas ni tildes. Además reconoce saludos y agradecimientos, y responde con datos actuales del sistema cuando se le pregunta qué especialidades atiende la clínica o cuánto cuesta la consulta de una especialidad. La conversación se guarda solo en la sesión del paciente, no en la base de datos, y se borra al cerrar la sesión o al presionar "Limpiar conversación". Las consultas no se registran en la bitácora.

## Precondición
El paciente ha iniciado sesión y tiene permiso para consultar el asistente virtual. Existen preguntas frecuentes activas registradas (CU23).

## Flujo principal
1. El paciente selecciona "Asistente virtual" en el panel principal.
2. El sistema muestra el saludo del asistente, los botones de categorías (Citas, Pagos, Notificaciones, Mi cuenta y La clínica), la conversación guardada en la sesión y el cuadro para escribir.
3. El paciente escribe su pregunta y presiona "Enviar".
4. El sistema valida que el mensaje no esté vacío y que no supere los 200 caracteres.
5. El sistema prepara el texto: lo pasa a minúsculas, le quita tildes y signos, lo separa en palabras e ignora las palabras comunes (de, la, el, que, como, mi, etc.).
6. El sistema busca la respuesta en este orden: (a) si es un saludo o un agradecimiento; (b) si pregunta qué especialidades hay, responde con las especialidades activas que tienen tarifa vigente; (c) si pregunta el precio de una especialidad, responde con el monto de su tarifa vigente; (d) si no, elige la pregunta frecuente activa con más coincidencias, donde una palabra clave coincidente vale más que una palabra del texto de la pregunta.
7. El sistema agrega la pregunta del paciente y la respuesta a la conversación y la muestra.

## Flujo alternativo
- **3a.** Si el paciente selecciona una categoría, el sistema muestra las preguntas activas de esa categoría. El paciente elige una y el sistema muestra su respuesta (paso 7).
- **3b.** Si el paciente presiona "Limpiar conversación", el sistema borra la conversación de la sesión y vuelve a mostrar el saludo.
- **4a.** Si el mensaje está vacío, el sistema muestra "Escriba su pregunta" y vuelve al paso 3.
- **4b.** Si el mensaje supera los 200 caracteres, el sistema muestra "La pregunta no puede superar los 200 caracteres" y vuelve al paso 3.
- **4c.** Si el paciente envía demasiadas preguntas en poco tiempo, el sistema muestra "Demasiadas consultas; espere un momento e intente de nuevo".
- **6a.** Si ninguna pregunta alcanza las coincidencias mínimas, el sistema responde "No entendí tu pregunta. Quizás buscabas:" con hasta 3 preguntas que tienen alguna coincidencia, o con los botones de categorías si no hay ninguna, y agrega "o comunícate con recepción".
- **6b.** Si se pregunta el precio de una especialidad que no existe o que no tiene tarifa vigente, el sistema responde "No encontré esa especialidad. Las especialidades disponibles son: …".

## Postcondición
El paciente recibe una respuesta y la conversación queda guardada en su sesión (como máximo los últimos 20 mensajes). No se modifica la base de datos.
