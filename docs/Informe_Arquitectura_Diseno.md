# Informe de Arquitectura y Diseño — Sistema de Noticias

## 1. Introducción

Este documento describe la arquitectura, los requisitos y el diseño técnico del proyecto **Sistema de Noticias**, desarrollado en PHP nativo con arquitectura MVC, MySQL como motor de base de datos, y aplicando los principios SOLID, DRY y las recomendaciones del OWASP Top 10.

## 2. Requisitos funcionales (RF)

| Código | Descripción |
|---|---|
| RF-01 | El sistema debe permitir a un administrador iniciar sesión mediante usuario y contraseña. |
| RF-02 | El sistema debe bloquear una cuenta tras 3 intentos fallidos de inicio de sesión durante 15 minutos. |
| RF-03 | El sistema debe registrar todos los intentos de inicio de sesión (usuario, IP, fecha/hora, resultado). |
| RF-04 | El administrador debe poder crear, listar, editar y desactivar usuarios del sistema. |
| RF-05 | El administrador debe poder crear, listar, editar y eliminar categorías de noticias. |
| RF-06 | El administrador debe poder crear, editar, listar y dar de baja noticias. |
| RF-07 | Toda noticia debe permitir la carga de un mínimo de 3 imágenes, generando automáticamente una miniatura por cada una. |
| RF-08 | El listado administrativo de noticias debe paginarse (5 por página) y permitir filtros por categoría, fecha, estado y texto de búsqueda. |
| RF-09 | El administrador debe poder responder, bloquear o eliminar comentarios de una noticia. |
| RF-10 | El administrador debe poder visualizar estadísticas de reacciones ("me gusta") por noticia. |
| RF-11 | El sitio público debe mostrar en la portada la noticia más reciente en formato destacado y las siguientes 2 más recientes como secundarias. |
| RF-12 | El sitio público debe ofrecer un listado completo de noticias publicadas, paginado, con filtro por categoría y buscador por título. |
| RF-13 | El sitio público debe permitir a cualquier visitante reaccionar con "me gusta" a una noticia (una vez por dirección IP). |
| RF-14 | El sitio público debe permitir a cualquier visitante comentar una noticia; el comentario queda pendiente de aprobación administrativa. |
| RF-15 | El sistema debe contabilizar y mostrar el número de visitantes del sitio público. |
| RF-16 | Todos los módulos (administrativo y público) deben ofrecer un enlace de regreso a la página de inicio. |
| RF-17 | El sistema debe soportar tres roles de usuario administrativo (admin, supervisor, editor) con permisos diferenciados: el editor solo crea/modifica sus propias noticias y no puede publicarlas; el supervisor puede crear, modificar y publicar cualquier noticia, y moderar comentarios (aprobar/bloquear/eliminar); el administrador tiene acceso total, incluyendo la gestión de usuarios y la respuesta a comentarios. |
| RF-18 | El panel administrativo debe permitir filtrar las estadísticas del dashboard por período de tiempo (últimos 7 días, 1, 3, 6 o 12 meses, o todo el tiempo), no solo en forma acumulada. |
| RF-19 | Toda noticia debe permitir asociar opcionalmente un video mediante enlace de YouTube o Vimeo, embebido automáticamente en el detalle público. |
| RF-20 | El detalle público de una noticia debe mostrar una imagen de cabecera destacada y el autor/usuario que la publicó. |
| RF-21 | El sitio público debe permitir reaccionar a una noticia con al menos 2-3 tipos de reacción distintos (cada uno con su propio icono), limitado a una reacción por visitante. |
| RF-22 | La búsqueda de noticias (administrativa y pública) debe ser insensible a mayúsculas/minúsculas. |
| RF-23 | El sitio público debe ofrecer una sección informativa ("Nosotros") con las tecnologías utilizadas, el valor/beneficios del proyecto y los datos de contacto del equipo desarrollador. |
| RF-24 | Además de la baja lógica (RF-06), un administrador o supervisor debe poder eliminar una noticia de forma permanente; al hacerlo, el sistema debe borrar también sus imágenes físicas del disco y sus comentarios/reacciones asociados. |

## 3. Requisitos no funcionales (RNF)

### RNF-01: Cumplimiento de OWASP Top 10
El sistema debe mitigar activamente los riesgos de seguridad más relevantes del OWASP Top 10, incluyendo (pero no limitado a): inyección SQL, fallas de autenticación, exposición de datos sensibles, CSRF y configuración incorrecta de seguridad.
**Implementación:** sentencias preparadas PDO (`core/Database.php`), hashing de contraseñas con `password_hash()` (`core/Security.php`), tokens CSRF en todos los formularios POST (`core/Security.php`, `core/BaseController.php`), manejo centralizado de errores sin exposición de detalles internos en producción (`core/ErrorHandler.php`).

### RNF-02: Principio DRY (Don't Repeat Yourself)
La lógica común no debe duplicarse entre módulos.
**Implementación:** `core/BaseModel.php` centraliza las operaciones CRUD genéricas (`all`, `find`, `create`, `update`, `delete`, `count`) reutilizadas por los 8 modelos del sistema. `core/BaseController.php` centraliza autenticación, verificación de roles, CSRF y utilidades de redirección/mensajes flash. `helpers/functions.php` centraliza el escape de salida, truncado de texto, formateo de fechas y construcción de paginación.

### RNF-03: Principios SOLID
El diseño orientado a objetos del sistema debe respetar los cinco principios SOLID.
**Implementación:** ver sección 2 ("Arquitectura") del `README.md` para el detalle de cada principio aplicado a clases concretas del proyecto.

### RNF-04: Política de bloqueo de cuentas
Una cuenta administrativa debe bloquearse automáticamente tras 3 intentos fallidos consecutivos de inicio de sesión, por un período de 15 minutos, y todo intento (exitoso o fallido) debe quedar registrado con IP y marca de tiempo.
**Implementación:** `UserModel::incrementFailedAttempts()`, `UserModel::lockAccount()`, `UserModel::isLocked()`, tabla `login_logs` poblada desde `AuthController::login()` en cada intento.

### RNF-05: Robustez de credenciales
Las contraseñas de usuarios administrativos deben tener entre 8 y 12 caracteres, combinando letras y números.
**Implementación:** `Validator::isValidPassword()`, aplicado en `UserController::save()` tanto en creación como en edición de usuarios.

### RNF-06: Integridad mediante firma digital
Toda noticia debe incluir un mecanismo de verificación de integridad que permita detectar si su contenido fue alterado fuera del flujo normal de la aplicación (por ejemplo, mediante una modificación directa en la base de datos).
**Implementación:** `Security::generateSignature()` calcula un HMAC-SHA256 sobre los campos `titulo`, `contenido`, `id_usuario` y `created_at` de cada noticia, usando una clave secreta de la aplicación (`SIGNATURE_SECRET_KEY`). La firma se almacena en el campo `firma_digital` y se recalcula/verifica en `NewsController::verifyIntegrity()`, mostrando el resultado en la vista de detalle administrativo de la noticia (`views/admin/news/detail.php`).

### RNF-07: Contrato de interfaz para servicios criptográficos
Las operaciones criptográficas de transformación de datos (hashing) deben unificarse bajo un mismo contrato de comportamiento, en vez de implementarse de forma dispersa o solo mediante llamadas directas a funciones nativas de PHP.
**Implementación:** `core/interfaces/HashServiceInterface.php` define el contrato (`hash()`/`verify()`). `core/PasswordHashService.php` (bcrypt) y `core/SignatureHashService.php` (HMAC-SHA256) lo implementan de forma intercambiable (SOLID - Inversión de Dependencias). `core/Security.php` actúa como fachada delgada que delega en ambos servicios, preservando su API estática ya usada en el resto del sistema.

### RNF-08: Control de acceso basado en roles (RBAC)
Cada acción administrativa debe validar en el servidor que el rol del usuario en sesión tenga el permiso correspondiente, además de ocultar en la interfaz las opciones no disponibles para ese rol.
**Implementación:** `core/BaseController.php::requireRole()` (ya existente, reutilizado por `UserController` y `CategoryController`); `NewsController::isPrivileged()`/`canModify()` para la restricción de edición/publicación de noticias por propiedad y rol; `CommentController::requireModerator()` y la verificación de rol `admin` en `reply()` para la moderación de comentarios; `views/partials/admin_menu.php` oculta las secciones sin permiso.

## 4. Diagrama de casos de uso

Ver [`Diagramas_UML.md`](Diagramas_UML.md#1-diagrama-de-casos-de-uso).

## 5. Diagrama de clases

Ver [`Diagramas_UML.md`](Diagramas_UML.md#2-diagrama-de-clases).

## 6. Diagrama de secuencia (inicio de sesión)

Ver [`Diagramas_UML.md`](Diagramas_UML.md#3-diagrama-de-secuencia-inicio-de-sesión).

## 7. Diagrama de estados (cuenta de usuario)

Ver [`Diagramas_UML.md`](Diagramas_UML.md#4-diagrama-de-estados-cuenta-de-usuario).

## 8. Diagrama entidad-relación (DER)

Ver [`Diagramas_UML.md`](Diagramas_UML.md#5-diagrama-entidad-relación-der).

### 8.1 Modelo de Dominio (UML) y flujos de interacción (IFML)

Ver [`Modelo_Dominio_IFML.md`](Modelo_Dominio_IFML.md): modelo de dominio UML limitado a las entidades de negocio (sin infraestructura), y diagramas de flujo de interacción (IFML) del sitio público y del panel administrativo, incluyendo las restricciones de navegación por rol descritas en RF-17 y RNF-08.

## 9. Diccionario de datos

### Tabla `usuarios`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único del usuario. |
| nombre | VARCHAR(100) | Nombre completo o usuario de acceso. |
| email | VARCHAR(150) | Correo electrónico, único, usado también para iniciar sesión. |
| password | VARCHAR(255) | Hash bcrypt de la contraseña (`password_hash`). |
| rol | ENUM('admin','editor','supervisor') | Rol del usuario dentro del sistema (ver RF-17). |
| activo | TINYINT(1) | 1 = activo, 0 = desactivado (no puede iniciar sesión). |
| intentos_fallidos | INT UNSIGNED | Contador de intentos fallidos consecutivos de login. |
| bloqueado_hasta | DATETIME (NULL) | Fecha/hora hasta la cual la cuenta permanece bloqueada. |
| created_at / updated_at | DATETIME | Marcas de tiempo de auditoría. |

### Tabla `categorias`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| nombre | VARCHAR(60) | Nombre de la categoría (único). |
| descripcion | VARCHAR(255) | Descripción opcional. |
| activo | TINYINT(1) | Estado de la categoría. |
| created_at | DATETIME | Fecha de creación. |

### Tabla `noticias`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| titulo | VARCHAR(200) | Título de la noticia. |
| contenido | TEXT | Cuerpo/párrafos de la noticia (HTML restringido). |
| id_usuario | INT UNSIGNED (FK → usuarios.id) | Usuario del sistema que creó la noticia. |
| autor | VARCHAR(120) (NULL) | Autor visible, opcional (puede diferir del usuario creador). |
| video_url | VARCHAR(255) (NULL) | Enlace de YouTube/Vimeo, embebido automáticamente en el detalle público (RF-19). |
| id_categoria | INT UNSIGNED (FK → categorias.id) | Categoría asociada. |
| publicado | TINYINT(1) | 1 = visible en el sitio público, 0 = borrador. |
| activo | TINYINT(1) | Baja lógica (1 = activa, 0 = dada de baja). |
| firma_digital | VARCHAR(64) | HMAC-SHA256 de integridad (RNF-06). |
| created_at / updated_at | DATETIME | Marcas de tiempo de auditoría. |

### Tabla `noticia_imagenes`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| id_noticia | INT UNSIGNED (FK → noticias.id) | Noticia a la que pertenece la imagen. |
| ruta_imagen | VARCHAR(255) | Ruta relativa de la imagen original. |
| ruta_thumbnail | VARCHAR(255) | Ruta relativa de la miniatura generada. |
| orden | INT UNSIGNED | Orden de despliegue dentro de la galería. |
| created_at | DATETIME | Fecha de carga. |

### Tabla `comentarios`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| id_noticia | INT UNSIGNED (FK → noticias.id) | Noticia comentada. |
| nombre_usuario | VARCHAR(100) | Nombre del visitante que comenta. |
| email | VARCHAR(150) | Correo del visitante. |
| comentario | TEXT | Contenido del comentario. |
| estado | ENUM('pendiente','aprobado','bloqueado') | Estado de moderación. |
| respuesta | TEXT (NULL) | Respuesta del administrador, si existe. |
| id_usuario_admin | INT UNSIGNED (FK → usuarios.id, NULL) | Administrador que respondió/moderó. |
| created_at | DATETIME | Fecha del comentario. |

### Tabla `reacciones`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| id_noticia | INT UNSIGNED (FK → noticias.id) | Noticia reaccionada. |
| tipo | VARCHAR(20) | Tipo de reacción: `like` (Me gusta), `eco` (Ecológica) o `interesante` (ver RF-21). |
| ip_address | VARCHAR(45) | IP del visitante (control de unicidad). |
| created_at | DATETIME | Fecha de la reacción. |

### Tabla `login_logs`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| usuario | VARCHAR(150) | Usuario o correo ingresado en el intento. |
| ip_address | VARCHAR(45) | Dirección IP de origen. |
| exito | TINYINT(1) | 1 = login exitoso, 0 = fallido. |
| user_agent | VARCHAR(255) | Navegador/cliente utilizado. |
| fecha_hora | DATETIME | Marca de tiempo del intento. |

### Tabla `visitas`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED (PK) | Identificador único. |
| session_id | VARCHAR(128) | Identificador de sesión PHP (deduplicación). |
| ip_address | VARCHAR(45) | Dirección IP del visitante. |
| created_at | DATETIME | Fecha de la visita. |

## 10. Conclusiones

El sistema cumple con los requisitos funcionales y no funcionales establecidos, aplicando una arquitectura MVC clara, principios de diseño orientado a objetos (SOLID/DRY) y controles de seguridad alineados con OWASP. La separación de responsabilidades entre `core/`, `models/`, `controllers/` y `views/` facilita el mantenimiento y la extensión futura del proyecto (por ejemplo, agregar nuevos tipos de contenido o roles de usuario) sin necesidad de modificar el código existente.
