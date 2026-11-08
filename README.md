# Clínica Vida Salud — Sistema web para la gestión de citas médicas

Sistema web que permite a la Clínica "Vida Salud" gestionar sus pacientes, médicos, especialidades,
horarios, citas médicas, pagos y caja, con reportes, bitácora de auditoría y asistencia al usuario
(asistente virtual y comandos de voz).

🌐 **Sistema en línea:** https://clinica-vida-salud.up.railway.app/

---

## Datos académicos

| | |
|---|---|
| **Universidad** | Universidad Autónoma Gabriel René Moreno (UAGRM) |
| **Facultad** | Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones |
| **Materia** | Ingeniería de Requisitos y Modelado |
| **Docente** | Ing. Angélica Garzón Cuéllar |
| **Grupo** | 15 |
| **Metodología** | PUDS (Proceso Unificado de Desarrollo de Software) con UML |

| Integrante | Registro |
|---|---|
| Gonzales Montero David Jherar | 224027591 |
| Porcel Calizaya Luis Fernando | 221045511 |

---

## Funcionalidades (casos de uso)

El sistema implementa **25 casos de uso** organizados en **8 paquetes**, desarrollados en dos ciclos.

| Paquete | Caso de uso | Ciclo |
|---|---|:---:|
| **Usuarios y acceso** | CU01 Gestionar usuarios y asignar roles | 1 |
| | CU02 Iniciar sesión | 1 |
| | CU03 Gestionar roles y permisos | 1 |
| | CU04 Configurar perfil de usuario | 1 |
| | CU05 Consultar bitácora | 1 |
| **Pacientes** | CU06 Gestionar paciente | 1 |
| | CU07 Gestionar perfil médico | 2 |
| | CU08 Consultar historial de citas | 2 |
| **Médicos y especialidades** | CU09 Gestionar especialidad | 1 |
| | CU10 Gestionar médico | 1 |
| | CU11 Gestionar horario de atención | 1 |
| **Citas** | CU12 Consultar disponibilidad | 1 |
| | CU13 Registrar cita | 1 |
| | CU14 Reprogramar cita | 1 |
| | CU15 Cancelar cita | 1 |
| | CU16 Controlar estado y asistencia de cita | 2 |
| **Agenda médica** | CU17 Consultar agenda médica | 2 |
| | CU18 Registrar atención de cita | 2 |
| **Reportes** | CU19 Generar reporte | 2 |
| **Pagos y caja** | CU20 Gestionar tarifas y métodos de pago | 2 |
| | CU21 Pagar cita | 2 |
| | CU22 Gestionar caja | 2 |
| **Asistencia al usuario** | CU23 Gestionar preguntas frecuentes | 2 |
| | CU24 Consultar asistente virtual | 2 |
| | CU25 Navegar por comando de voz | 2 |

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 con Laravel 12 |
| Vistas | Blade + Bootstrap 5.3 + JavaScript |
| Base de datos | PostgreSQL |
| Pagos en línea | Stripe (modo de prueba) |
| Correo y notificaciones | Brevo (correo) y Web Push |
| Despliegue | Docker + Railway |

---

## Arquitectura

Aplicación **monolítica** con patrón **MVC** (Modelo – Vista – Controlador). El código está organizado
**igual que el diagrama de paquetes** del análisis: cada paquete tiene su carpeta, y dentro de ella
una carpeta por caso de uso. Así cada clase del diseño se encuentra directamente en el código:

| En los diagramas | En el código |
|---|---|
| Paquete | `app/Modulos/<paquete>/` |
| `UI_` (interfaz) | Vista Blade en `resources/views/<paquete>/<caso_de_uso>/` |
| `Ctrl_` (controlador) | Controlador en `app/Modulos/<paquete>/controller/<caso_de_uso>/` |
| Entidad | Modelo en `app/Modulos/<paquete>/models/` (una tabla de la base de datos) |

---

## Estructura del repositorio

```
clinica-vida-salud/
├── app/Modulos/                 Código por paquete
│   ├── usuarios_acceso/
│   ├── pacientes/
│   ├── medicos_especialidades/
│   ├── citas/
│   ├── agenda_medica/
│   ├── reportes/
│   ├── pagos/
│   └── asistencia_usuario/
│       ├── controller/          Controladores, una carpeta por caso de uso
│       ├── models/              Modelos (entidades)
│       └── service/             Lógica de negocio compleja
├── resources/views/             Vistas Blade, por paquete y caso de uso
├── routes/modulos/              Rutas de cada paquete
├── config/modulos.php           Menú del sistema y permisos de cada opción
├── database/sql/                Script de la base de datos y sus actualizaciones
├── docs/                        Documentación PUDS
│   ├── arquitectura/            Diagramas de paquetes y casos de uso generales
│   ├── base_de_datos/           Diagrama de clases y diccionario de datos
│   └── casos_de_uso/            Por caso de uso: detallar y diagramas (.puml)
├── public/                      CSS, JavaScript e imágenes
├── docker/ y Dockerfile         Imagen para el despliegue en Railway
└── README.md
```

---

## Ramas de trabajo (Git)

| Rama | Uso |
|---|---|
| `main` | Versión estable; es la que se despliega en Railway |
| `dev-prueba` | Integración y pruebas del trabajo de ambos integrantes |
| `dev-jherar` | Trabajo de David Jherar Gonzales Montero |
| `dev-fernando` | Trabajo de Luis Fernando Porcel Calizaya |

Flujo: **rama personal → Pull Request a `dev-prueba` → pruebas → Pull Request a `main`**.
