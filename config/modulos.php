<?php

/*
|--------------------------------------------------------------------------
| Módulos del menú
|--------------------------------------------------------------------------
| Cada módulo aparece en el menú solo si el rol del usuario tiene al menos
| uno de sus permisos. "ruta" es el nombre de la ruta (routes/modulos/<paquete>.php);
| mientras un módulo no esté implementado se deja en null y se muestra
| como "Próximamente".
*/

return [
    // Usuarios y acceso
    ['titulo' => 'Usuarios',            'icono' => 'bi-people',            'cu' => 'CU01', 'permisos' => ['usuarios.gestionar'],       'ruta' => 'usuarios.index'],
    ['titulo' => 'Roles y permisos',    'icono' => 'bi-shield-lock',       'cu' => 'CU03', 'permisos' => ['roles.gestionar'],          'ruta' => 'roles.index'],
    ['titulo' => 'Bitácora',            'icono' => 'bi-journal-text',      'cu' => 'CU05', 'permisos' => ['bitacora.consultar'],       'ruta' => 'bitacora.index'],

    // Médicos, especialidades y horarios
    ['titulo' => 'Especialidades',      'icono' => 'bi-clipboard2-pulse',  'cu' => 'CU09', 'permisos' => ['especialidades.gestionar'], 'ruta' => 'especialidades.index'],
    ['titulo' => 'Médicos',             'icono' => 'bi-person-badge',      'cu' => 'CU10', 'permisos' => ['medicos.gestionar'],        'ruta' => 'medicos.index'],
    ['titulo' => 'Horarios de atención','icono' => 'bi-calendar-week',     'cu' => 'CU11', 'permisos' => ['horarios.gestionar'],       'ruta' => null],

    // Pacientes
    ['titulo' => 'Pacientes',           'icono' => 'bi-person-vcard',      'cu' => 'CU06', 'permisos' => ['pacientes.gestionar'],      'ruta' => null],
    ['titulo' => 'Perfil médico',       'icono' => 'bi-heart-pulse',       'cu' => 'CU07', 'permisos' => ['perfil_medico.gestionar'],  'ruta' => null],
    ['titulo' => 'Historial de citas',  'icono' => 'bi-clock-history',     'cu' => 'CU08', 'permisos' => ['historial.consultar'],      'ruta' => null],

    // Citas
    ['titulo' => 'Disponibilidad',      'icono' => 'bi-calendar-check',    'cu' => 'CU12', 'permisos' => ['disponibilidad.consultar'], 'ruta' => null],
    ['titulo' => 'Citas',               'icono' => 'bi-calendar-plus',     'cu' => 'CU13-16',
        'permisos' => ['citas.registrar', 'citas.reprogramar', 'citas.cancelar', 'citas.controlar_estado'], 'ruta' => null],

    // Agenda y reportes
    ['titulo' => 'Agenda médica',       'icono' => 'bi-calendar3',         'cu' => 'CU17-18',
        'permisos' => ['agenda.consultar', 'citas.registrar_atencion'], 'ruta' => null],
    ['titulo' => 'Reportes',            'icono' => 'bi-bar-chart',         'cu' => 'CU19', 'permisos' => ['reportes.generar'],         'ruta' => null],
];
