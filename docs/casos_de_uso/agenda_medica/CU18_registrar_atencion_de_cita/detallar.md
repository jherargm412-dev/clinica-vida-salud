# CU18 Registrar Atención de Cita

| Campo | Contenido |
|---|---|
| Caso de uso | CU18 Registrar Atención de Cita |
| Paquete | agenda_medica |
| Permiso | `citas.registrar_atencion` |
| Actor | Médico con ficha médica asociada |

## Propósito
Registrar el diagnóstico y las notas clínicas de una cita efectivamente iniciada.

## Flujo principal
1. El médico abre "Registrar atención".
2. El sistema lista citas del médico en estado Programada, Confirmada o Atendida, que ya comenzaron y no tienen atención clínica registrada.
3. El médico selecciona una cita.
4. El sistema valida la asociación médico-cita y muestra los datos de la cita, paciente y perfil médico disponible.
5. El médico ingresa el diagnóstico obligatorio y, opcionalmente, notas clínicas.
6. El servidor vuelve a validar la cita, su hora de inicio y que no exista atención previa.
7. El sistema guarda la atención en una transacción. Si la cita estaba Programada o Confirmada, pasa a Atendida; si ya estaba Atendida por CU16, conserva ese estado.
8. La bitácora registra `REGISTRAR_ATENCION` en `atencion_medica` e identifica el código de cita, sin guardar diagnóstico ni notas en la bitácora.

## Reglas y alternativas
- Cada cita admite como máximo una atención clínica.
- El diagnóstico es obligatorio (máximo 500 caracteres); las notas son opcionales (máximo 2000).
- No se permite atender antes del inicio de la cita, registrar citas ajenas al médico ni atender estados no elegibles.
- La restricción única de `atencion_medica.id_cita` complementa la validación transaccional.
- Si ya existe atención o la cita dejó de ser elegible, no se guardan datos parciales y se muestra un mensaje.

## Postcondición
La atención clínica queda guardada una sola vez, la cita refleja el estado correcto y la bitácora no contiene información clínica sensible.
