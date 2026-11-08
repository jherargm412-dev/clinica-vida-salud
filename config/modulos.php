<?php

/*
|--------------------------------------------------------------------------
| Módulos del menú
|--------------------------------------------------------------------------
| El menú lateral se arma por grupos (uno por paquete, en el orden del
| diagrama de paquetes, salvo Agenda médica y Reportes, que van al final).
| Cada grupo se despliega y muestra sus opciones.
|
| "grupos":   clave del paquete => título e ícono del grupo (este es el orden del menú).
| "opciones": cada opción aparece solo si el rol del usuario tiene al menos uno de sus
|             "permisos". "grupo" es la clave del grupo donde se muestra.
|             "ruta" es el nombre de la ruta (routes/modulos/<paquete>.php); mientras
|             un caso de uso no esté implementado se deja en null y se muestra como
|             "Próximamente".
|             "perfil" (opcional): requiere ficha vinculada de paciente o médico.
|             "activo" (opcional): patrones de rutas que resaltan la opción en el menú.
|             Si no se indica, se usa el prefijo de la ruta (p. ej. "usuarios.*").
*/

return [
    'grupos' => [
        'usuarios_acceso'        => ['titulo' => 'Usuarios y acceso',        'icono' => 'bi-shield-lock'],
        'pacientes'              => ['titulo' => 'Pacientes',                'icono' => 'bi-person-vcard'],
        'medicos_especialidades' => ['titulo' => 'Médicos y especialidades', 'icono' => 'bi-person-badge'],
        'citas'                  => ['titulo' => 'Citas',                    'icono' => 'bi-calendar-check'],
        'pagos'                  => ['titulo' => 'Pagos y caja',             'icono' => 'bi-wallet2'],
        'asistencia_usuario'     => ['titulo' => 'Asistencia al usuario',    'icono' => 'bi-chat-dots'],
        // Agenda médica y Reportes van al final: suelen tener una sola opción (enlace directo, sin desplegable)
        'agenda_medica'          => ['titulo' => 'Agenda médica',            'icono' => 'bi-calendar3'],
        'reportes'               => ['titulo' => 'Reportes',                 'icono' => 'bi-bar-chart'],
    ],

    'opciones' => [
        // Usuarios y acceso
        ['grupo' => 'usuarios_acceso', 'titulo' => 'Usuarios',         'icono' => 'bi-people',       'cu' => 'CU01', 'permisos' => ['usuarios.gestionar'], 'ruta' => 'usuarios.index'],
        ['grupo' => 'usuarios_acceso', 'titulo' => 'Roles y permisos', 'icono' => 'bi-shield-lock',  'cu' => 'CU03', 'permisos' => ['roles.gestionar'],    'ruta' => 'roles.index'],
        ['grupo' => 'usuarios_acceso', 'titulo' => 'Bitácora',         'icono' => 'bi-journal-text', 'cu' => 'CU05', 'permisos' => ['bitacora.consultar'], 'ruta' => 'bitacora.index'],

        // Pacientes
        ['grupo' => 'pacientes', 'titulo' => 'Gestionar pacientes', 'icono' => 'bi-person-vcard',  'cu' => 'CU06', 'permisos' => ['pacientes.gestionar'],     'ruta' => 'pacientes.index'],
        ['grupo' => 'pacientes', 'titulo' => 'Perfil médico',      'icono' => 'bi-heart-pulse',   'cu' => 'CU07', 'permisos' => ['perfil_medico.gestionar'], 'perfil' => 'paciente', 'ruta' => 'perfil_medico.mostrar'],
        ['grupo' => 'pacientes', 'titulo' => 'Historial de citas', 'icono' => 'bi-clock-history', 'cu' => 'CU08', 'permisos' => ['historial.consultar'],     'ruta' => 'historial.index'],

        // Médicos, especialidades y horarios
        ['grupo' => 'medicos_especialidades', 'titulo' => 'Especialidades',       'icono' => 'bi-clipboard2-pulse', 'cu' => 'CU09', 'permisos' => ['especialidades.gestionar'], 'ruta' => 'especialidades.index'],
        ['grupo' => 'medicos_especialidades', 'titulo' => 'Médicos',              'icono' => 'bi-person-badge',     'cu' => 'CU10', 'permisos' => ['medicos.gestionar'],        'ruta' => 'medicos.index'],
        ['grupo' => 'medicos_especialidades', 'titulo' => 'Horarios de atención', 'icono' => 'bi-calendar-week',    'cu' => 'CU11', 'permisos' => ['horarios.gestionar'],       'ruta' => 'horarios.index'],

        // Citas
        ['grupo' => 'citas', 'titulo' => 'Disponibilidad',   'icono' => 'bi-calendar-check', 'cu' => 'CU12', 'permisos' => ['disponibilidad.consultar'], 'ruta' => 'disponibilidad.index'],
        ['grupo' => 'citas', 'titulo' => 'Registrar cita',   'icono' => 'bi-calendar-plus',  'cu' => 'CU13', 'permisos' => ['citas.registrar'],          'ruta' => 'citas.crear',
            'activo' => ['citas.crear', 'citas.guardar', 'citas.confirmacion']],
        ['grupo' => 'citas', 'titulo' => 'Reprogramar cita', 'icono' => 'bi-calendar2-week', 'cu' => 'CU14', 'permisos' => ['citas.reprogramar'],        'ruta' => 'citas.reprogramar.index'],
        ['grupo' => 'citas', 'titulo' => 'Cancelar cita',    'icono' => 'bi-calendar-x',     'cu' => 'CU15', 'permisos' => ['citas.cancelar'],           'ruta' => 'citas.cancelar.index'],
        ['grupo' => 'citas', 'titulo' => 'Controlar estado de citas', 'icono' => 'bi-clipboard-check', 'cu' => 'CU16',
            'permisos' => ['citas.controlar_estado'], 'ruta' => 'citas.estado.index'],

        // Agenda médica
        ['grupo' => 'agenda_medica', 'titulo' => 'Agenda médica', 'icono' => 'bi-calendar3', 'cu' => 'CU17',
            'permisos' => ['agenda.consultar'], 'ruta' => 'agenda.index'],
        ['grupo' => 'agenda_medica', 'titulo' => 'Registrar atención', 'icono' => 'bi-clipboard2-pulse', 'cu' => 'CU18',
            'permisos' => ['citas.registrar_atencion'], 'perfil' => 'medico', 'ruta' => 'atencion.index'],

        // Reportes
        ['grupo' => 'reportes', 'titulo' => 'Reportes', 'icono' => 'bi-bar-chart', 'cu' => 'CU19',
            'permisos' => ['reportes.generar', 'reportes.generar_todos'], 'ruta' => 'reportes.index'],

        // Pagos y caja
        ['grupo' => 'pagos', 'titulo' => 'Tarifas y métodos de pago', 'icono' => 'bi-cash-coin', 'cu' => 'CU20', 'permisos' => ['pagos.configurar'], 'ruta' => 'tarifas.index',
            'activo' => ['tarifas.*', 'metodos_pago.*']],
        ['grupo' => 'pagos', 'titulo' => 'Pagar cita', 'icono' => 'bi-credit-card', 'cu' => 'CU21',
            'permisos' => ['pagos.cobrar', 'pagos.pagar_propia'], 'ruta' => 'pagos.index'],
        ['grupo' => 'pagos', 'titulo' => 'Caja', 'icono' => 'bi-safe', 'cu' => 'CU22', 'permisos' => ['caja.gestionar'], 'ruta' => 'caja.index'],

        // Asistencia al usuario. CU25 se usa con el micrófono de la barra superior; su opción del menú
        // solo muestra la lista de comandos disponibles (flujo 6b).
        ['grupo' => 'asistencia_usuario', 'titulo' => 'Preguntas frecuentes', 'icono' => 'bi-question-circle', 'cu' => 'CU23',
            'permisos' => ['faq.gestionar'], 'ruta' => 'faq.index', 'activo' => ['faq.*']],
        ['grupo' => 'asistencia_usuario', 'titulo' => 'Asistente virtual', 'icono' => 'bi-robot', 'cu' => 'CU24',
            'permisos' => ['asistente.consultar'], 'ruta' => 'asistente.mostrar', 'activo' => ['asistente.*']],
        ['grupo' => 'asistencia_usuario', 'titulo' => 'Comandos de voz', 'icono' => 'bi-mic', 'cu' => 'CU25',
            'permisos' => ['voz.usar'], 'ruta' => 'voz.ayuda', 'activo' => ['voz.*']],
    ],
];
