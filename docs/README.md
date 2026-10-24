# Documentación del proyecto — Clínica Vida Salud

Documentación PUDS en texto (Markdown y PlantUML) para consultarla al programar. Las imágenes de los diagramas están en el documento oficial.

Para ver un `.puml` como imagen en VS Code: extensión **PlantUML** → `Alt + D` (vista previa).

## Contenido

| Carpeta / archivo | Qué contiene |
|---|---|
| `decisiones.md` | Decisiones de diseño vigentes (reglas de negocio acordadas) |
| `arquitectura/` | Paquetes del sistema, sus casos de uso y el diagrama general de casos de uso |
| `base_de_datos/` | Diagrama de clases de la BD (`clases_bd.puml`) y diccionario de datos (`diccionario.md`) |
| `casos_de_uso/<paquete>/<CU>/` | Por caso de uso: `detallar.md` y sus diagramas |

## Archivos de cada caso de uso

| Archivo | Diagrama |
|---|---|
| `detallar.md` | Tabla del caso de uso (propósito, flujos, alternativos, pre y postcondición) |
| `caso_de_uso.puml` | Diagrama de caso de uso |
| `comunicacion_<escenario>.puml` | Diagrama de comunicación de un escenario (análisis) |
| `comunicacion_CRUD.puml` | Diagrama de comunicación con todas las operaciones |
| `clases_analisis.puml` | Diagrama de clases de análisis |
| `secuencia_<escenario>.puml` | Diagrama de secuencia de un escenario (diseño) |
| `secuencia_CRUD.puml` | Diagrama de secuencia con todas las operaciones |

Los casos de uso con un solo escenario tienen `comunicacion.puml` y `secuencia.puml`.

## Casos de uso documentados

| Paquete | Caso de uso | Responsable |
|---|---|---|
| usuarios_acceso | CU01 Gestionar Usuarios y Asignar Roles | Jherar |
| usuarios_acceso | CU02 Iniciar Sesión | Jherar |
| usuarios_acceso | CU03 Gestionar Roles y Permisos | Jherar |
| usuarios_acceso | CU04 Configurar Perfil de Usuario | Jherar |
| usuarios_acceso | CU05 Consultar Bitácora | Jherar |
| medicos_especialidades | CU09 Gestionar Especialidad | Jherar |
| medicos_especialidades | CU10 Gestionar Médico | Jherar |

Los casos de uso de Luis Fernando (CU06, CU11–CU15) los agrega él con la misma estructura.
