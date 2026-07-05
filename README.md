# Sistema Médico Portable:
## Descripción
Sistema web desarrollado en **PHP** con base de datos **SQLite** para la gestión de citas médicas. El proyecto funciona de manera portable, permitiendo ejecutarlo localmente sin necesidad de instalar un servidor web externo como XAMPP o WAMP.

## Características

* Inicio de sesión para el sistema.
* Registro de citas médicas.
* Gestión de pacientes.
* Selección de especialidades médicas.
* Asignación automática de horarios disponibles.
* Restricción de citas a días hábiles.
* Base de datos SQLite integrada.
* Interfaz sencilla e intuitiva.
* Sistema portable para Windows.

---

##  Tecnologías utilizadas

* PHP 8
* SQLite
* HTML5
* CSS3
* JavaScript
* PDO (PHP Data Objects)

---

## Requisitos

### Requisitos mínimos de hardware

| Recurso                 | Requerimiento mínimo                 |
| ----------------------- | ------------------------------------ |
| **Procesador**          | 1 GHz o superior                     |
| **Memoria RAM**         | 2 GB                                 |
| **Espacio en disco**    | 200 MB libres (incluye PHP portable) |
| **Conexión a Internet** | No requerida (funciona en local)     |

### Requisitos de software

| Componente            | Detalle                                                             |
| --------------------- | ------------------------------------------------------------------- |
| **Sistema Operativo** | Windows 7, 8, 10 u 11 (64 bits)                                     |
| **Servidor PHP**      | PHP 8.x Portable (incluido en la carpeta `/php`)                    |
| **Base de datos**     | SQLite 3 (no requiere instalación aparte, se gestiona mediante PDO) |
| **Extensión PHP**     | `pdo_sqlite` habilitada en `php.ini`                                |
| **Navegador web**     | Google Chrome, Microsoft Edge o Mozilla Firefox actualizados        |

---

## Ejecución

1. Descargar o clonar este repositorio.

```bash
git clone https://github.com/USUARIO/NOMBRE-REPOSITORIO.git
```

2. Abrir la carpeta del proyecto.

3. Ejecutar el archivo:

```text
EJECUTAR_SISTEMA.bat
```

4. El sistema iniciará el servidor PHP local.

5. Abrir el navegador y acceder a:

```text
http://localhost:8000
```

*(El puerto puede variar dependiendo de la configuración del archivo BAT.)*

---

##  Base de datos

El sistema utiliza una base de datos SQLite llamada:

```text
citas_medicas.db
```

---

## Credenciales de acceso

**Usuario**

```text
GRUPO_H
```

**Contraseña**

```text
GRUPO H
```

---

## Funcionalidades principales

* Inicio de sesión.
* Registro de pacientes.
* Programación de citas médicas.
* Validacion de datos personale
* Validación de horarios ocupados.
* Selección automática de horas disponibles.
* Organización por especialidad.
* Almacenamiento persistente mediante SQLite.

---

## Autores

Desarrollado como proyecto académico por el Grupo H de la matteria de Interaccion Humano Computador.

---

## Licencia

Este proyecto se distribuye únicamente con fines educativos.
