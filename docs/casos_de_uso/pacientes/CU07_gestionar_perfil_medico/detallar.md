# CU07 Gestionar Perfil Médico

| Campo | Contenido |
|---|---|
| Caso de uso | CU07 Gestionar Perfil Médico |
| Paquete | pacientes |
| Permiso | `perfil_medico.gestionar` |
| Actor | Paciente con ficha de paciente asociada a su cuenta |

## Propósito
Permitir al paciente mantener su información médica básica y el contacto de emergencia para facilitar su atención.

## Flujo principal
1. El paciente abre "Perfil médico" desde el menú.
2. El sistema obtiene la ficha de paciente vinculada a la cuenta y muestra el perfil médico existente o un formulario vacío.
3. El paciente puede indicar tipo de sangre, alergias, enfermedades crónicas, medicamentos y datos de contacto de emergencia.
4. El sistema valida los campos y exige que nombre, parentesco y teléfono del contacto estén completos conjuntamente.
5. El sistema crea o actualiza el perfil y registra la acción `ACTUALIZAR_PERFIL_MEDICO` en la bitácora sin copiar información clínica a esta.
6. El sistema confirma que el perfil fue actualizado.

## Reglas y alternativas
- Todos los campos son opcionales; el tipo de sangre debe pertenecer a la lista admitida.
- Cada texto médico admite hasta 500 caracteres; el teléfono admite entre 7 y 20 caracteres numéricos, espacios, `+` o `-`.
- Si la cuenta no está vinculada a una ficha de paciente, no se puede administrar un perfil.
- Los datos inválidos se conservan para corregirlos y no se guardan.

## Postcondición
Existe como máximo un perfil por paciente, con la última información válida y su fecha de actualización.
