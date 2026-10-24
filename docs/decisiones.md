# Decisiones de diseño vigentes

## Actores y roles
- Roles: Administrador, Recepcionista, Médico y Paciente. Todos heredan del actor Usuario (CU02 Iniciar sesión y CU04 Gestionar perfil los usa cualquier usuario).
- Los permisos son fijos por rol (tabla `rol_permiso`); hay un permiso por caso de uso, con una clave (`usuarios.gestionar`, `especialidades.gestionar`, `medicos.gestionar`, `citas.registrar`, etc.).
- El rol Administrador no se puede deshabilitar ni perder el permiso de gestionar roles. Un rol con usuarios activos no se puede deshabilitar.

## Datos personales y cuentas
- Nombres, apellidos y correo se guardan **solo en Usuario**. Médico y Paciente tienen una cuenta de usuario obligatoria (Usuario 1 — 0..1 Médico/Paciente) y toman de ella esos datos.
- El correo es obligatorio para el personal (validado en PHP) y opcional para pacientes.
- Contraseñas con hash seguro (`password_hash` / `Hash::make`), mínimo 8 caracteres.
- Tras 3 intentos fallidos consecutivos la cuenta se bloquea; solo el administrador la desbloquea (CU01).
- CU06 crea la cuenta del paciente: usuario = CI, contraseña temporal de 8 caracteres mostrada en pantalla (envío por correo en el Ciclo 2).

## Bajas y estados
- No se borran registros con historial: se desactivan (`activo = false`) usuarios, roles, especialidades, médicos, pacientes y horarios.
- Las tablas puente (`rol_permiso`, `medico_especialidad`) se eliminan o sincronizan al modificar.
- Las citas cambian de estado: Programada, Confirmada, Atendida, Cancelada o Inasistencia.
- La bitácora nunca se modifica ni se elimina.

## Médicos, especialidades y horarios
- Especialidad: nombre único sin distinguir mayúsculas ni espacios (índice `LOWER(TRIM(nombre))`). Una especialidad inactiva no se ofrece para nuevos médicos ni citas.
- Especialidad: el sistema guarda el nombre en formato tipo título ("peDiaTRía" → "Pediatría"; "de", "del", "y"… en minúscula). Se hace en `EspecialidadRequest::prepareForValidation()`. La validación de duplicados ignora mayúsculas, tildes y espacios.
- Médico: código generado (`MED-0001`), registro profesional único, al menos una especialidad, cuenta de usuario con rol Médico. Un médico inactivo no recibe nuevas citas pero conserva su historial.
- Médico ◆— HorarioAtencion es composición (el horario no existe sin su médico).
- Duración de cita por defecto: 30 minutos.

## Citas
- Un médico no puede tener dos citas vigentes a la misma fecha y hora (índice único parcial `WHERE estado <> 'Cancelada'`).
- Las citas atendidas o canceladas no se reprograman; se registra una nueva.
- El paciente es actor de CU12 y CU13 (modelado en el Ciclo 1; su pantalla se implementa en el Ciclo 2).

## Arquitectura
- Laravel monolítico con Blade, sin API (indicado por el auxiliar).
- Código organizado por paquetes (`app/Modulos/<paquete>`), igual que el diagrama de paquetes.
- Base de datos creada con el script SQL; no se usan migraciones de Laravel.

## Usuarios (CU01)
- En CU01 solo se asignan roles activos **excepto Paciente** (las cuentas de pacientes se crean en el CU06 junto con su ficha). Al editar una cuenta se muestra también su rol actual.
- Correo opcional; si se ingresa, debe ser único. Contraseña obligatoria al registrar; al modificar, si se deja vacía se mantiene.
- Política de contraseñas (CU01, CU04, CU06): mínimo 8 caracteres con al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial (ej.: `Clave123!`). Se pide confirmar la contraseña (campo `contrasena_confirmation`). Centralizada en `app/Modulos/compartido/Contrasena.php`.
- Nombre de usuario y correo se guardan en minúsculas; el login no distingue mayúsculas. Nombres y apellidos en formato tipo título (`app/Modulos/compartido/Texto.php`, compartido con especialidades y pacientes).
- El administrador no puede desactivar su propia cuenta.
