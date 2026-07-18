# Sistema de Noticias

Proyecto final desarrollado en **PHP nativo (8.x) + MySQL**, siguiendo arquitectura **MVC**, principios **SOLID** y **DRY**, y las buenas prácticas de seguridad del **OWASP Top 10**.

## Integrantes
Jose Barahona, 8-939-51

Luis Jiménez, 8-1018-1285

Brian Lee 8-1031-2047 

Shirley Wen, 8-957-1526

___

**Materia:** Desarrollo de Software VII

**Docente:** Irina Fong

**Fecha de entrega:** 22/7/26

***

## Enlace al video de demostración

> _[Agregar aquí el enlace al video de demostración (máximo 10 minutos)]_

---

## 1. Descripción general

Sistema de Noticias es una aplicación web que permite administrar y publicar noticias organizadas por categorías, con gestión de comentarios, reacciones ("me gusta") y un panel administrativo completo. Cuenta con dos módulos:

- **Módulo administrativo**: login seguro, CRUD de usuarios, categorías y noticias (con subida de imágenes y miniaturas automáticas), gestión de comentarios y estadísticas de reacciones.
- **Módulo público**: portada con las noticias más recientes, listado completo con filtros y buscador, detalle de noticia con comentarios y reacciones, y contador de visitantes.

## 2. Arquitectura

El proyecto sigue una arquitectura **MVC** clásica en PHP nativo, organizada así:

```
/ (raíz del proyecto)
├── config/              # Configuración: credenciales de BD, constantes, bootstrap
├── core/                # Clases núcleo: Database, Validator, Security, ErrorHandler,
│                        # BaseModel, BaseController, ImageUploader, interfaces/
├── helpers/             # Funciones auxiliares reutilizables (DRY)
├── models/              # Modelos (uno por entidad), extienden BaseModel
├── controllers/          # Controladores (uno por módulo), extienden BaseController
├── views/
│   ├── admin/           # Vistas del panel administrativo
│   └── public/          # Vistas del sitio público
├── public/
│   ├── css/, js/        # Recursos estáticos
│   └── uploads/         # Imágenes de noticias y miniaturas subidas
├── logs/                # Registro de errores de la aplicación
├── docs/                # Documentación técnica y diagramas UML
├── backup.sql           # Script de base de datos (estructura + datos de prueba)
└── index.php            # Punto de entrada del sitio público
```

Cada vista (`views/**/*.php`) actúa como punto de entrada: incluye `config/bootstrap.php`, instancia el controlador correspondiente y renderiza el resultado. Los controladores contienen la lógica de negocio y validación; los modelos encapsulan el acceso a datos mediante PDO con sentencias preparadas.

### Principios aplicados

- **SOLID**
  - *S (Responsabilidad única)*: `Database` solo maneja la conexión, `Validator` solo valida/sanitiza, `Security` solo trata temas criptográficos/CSRF, `ImageUploader` solo procesa imágenes.
  - *O (Abierto/cerrado)*: `ErrorHandlerInterface` y `CrudInterface` permiten extender comportamiento sin modificar el código existente.
  - *L (Sustitución de Liskov)*: todos los modelos concretos (`UserModel`, `NewsModel`, etc.) heredan de `BaseModel` y cumplen el contrato `CrudInterface` de forma intercambiable.
  - *I (Segregación de interfaces)*: `CrudInterface` y `ErrorHandlerInterface` son contratos pequeños y específicos.
  - *D (Inversión de dependencias)*: los controladores dependen de abstracciones (interfaces) en vez de implementaciones concretas.
- **DRY**: `BaseModel` centraliza las operaciones CRUD genéricas; `BaseController` centraliza autenticación, CSRF y utilidades comunes; `helpers/functions.php` evita duplicar lógica de presentación (escape, truncado, paginación, fechas).

## 3. Requisitos de instalación

- **WAMP64** (o equivalente) con:
  - PHP 8.0 o superior (extensiones requeridas: `pdo_mysql`, `gd`, `fileinfo`, `session`)
  - MySQL 5.7+ / MariaDB
  - Apache 2.4+
- Navegador web moderno.

## 4. Guía de despliegue

1. **Copiar el proyecto** a la carpeta de WAMP, por ejemplo:
   ```
   C:\wamp64\www\ProyectoFinal_DS7\
   ```
2. **Crear la base de datos** importando el script `backup.sql`:
   - Desde phpMyAdmin: crear conexión y usar **Importar** → seleccionar `backup.sql`.
   - Desde línea de comandos:
     ```bash
     mysql -u root -p < backup.sql
     ```
   El script crea la base de datos `sistema_noticias`, todas las tablas, relaciones, índices y los datos de prueba (incluyendo el usuario administrador).
3. **Configurar credenciales de conexión** (si son distintas a las de un WAMP estándar) en:
   ```
   config/database.php
   ```
   Por defecto usa `host=127.0.0.1`, `usuario=root`, `contraseña=` (vacía), `base de datos=sistema_noticias`.
4. **Verificar permisos de escritura** en las carpetas:
   ```
   public/uploads/news/
   public/uploads/thumbnails/
   logs/
   ```
5. **Iniciar los servicios de WAMP** (Apache + MySQL) y acceder a:
   ```
   http://localhost/ProyectoFinal_DS7/
   ```
   El panel administrativo se encuentra en:
   ```
   http://localhost/ProyectoFinal_DS7/views/admin/login.php
   ```

> La aplicación calcula automáticamente su URL base a partir de la ubicación del proyecto, por lo que funciona sin cambios sin importar el nombre de la carpeta dentro de `www/`.

## 5. Credenciales de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `root2514` | Administrador (acceso total, incluyendo gestión de usuarios) |
| `editor@sistemanoticias.local` | `Editor2024` | Editor (gestión de noticias, categorías y comentarios) |

## 6. Medidas de seguridad implementadas (OWASP Top 10)

| Riesgo OWASP | Medida implementada |
|---|---|
| **A02 – Fallas criptográficas** | Contraseñas almacenadas con `password_hash()` (bcrypt, costo 12), nunca en texto plano. |
| **A03 – Inyección** | Todas las consultas usan sentencias preparadas PDO con parámetros vinculados (`PDO::ATTR_EMULATE_PREPARES = false`), a través de `Database::run()` y `BaseModel`. |
| **A01 / CSRF** | Token CSRF único por sesión (`Security::generateCsrfToken()`), validado en **todas** las peticiones POST mediante `BaseController::requireCsrf()`. |
| **A07 – Fallas de identificación y autenticación** | Política de bloqueo de cuenta tras 3 intentos fallidos (15 minutos), registro de todos los intentos de login con IP, fecha/hora y user-agent en `login_logs`. |
| **A05 – Configuración de seguridad incorrecta** | `ErrorHandler` centralizado que evita filtrar rutas, queries o *stack traces* al usuario final en producción; cookies de sesión `HttpOnly` + `SameSite=Lax`. |
| **Validación de entradas** | Clase `Validator` sanitiza (`strip_tags`, `filter_var`) y valida (longitud, formato, tipo) todos los datos de entrada antes de procesarlos. |
| **Control de acceso** | `BaseController::requireAuth()` / `requireRole()` protegen cada controlador administrativo; el CRUD de usuarios está restringido al rol `admin`. |
| **Integridad de datos (RNF-06)** | Cada noticia almacena una **firma digital** (`HMAC-SHA256`) calculada sobre sus campos clave (`titulo|contenido|id_usuario|created_at`). El detalle administrativo de la noticia verifica esta firma y advierte si el contenido fue alterado directamente en la base de datos. |
| **Política de contraseñas (RNF-05)** | Entre 8 y 12 caracteres, combinando letras y números, validado en `Validator::isValidPassword()`. |
| **Subida de archivos** | Validación de tipo MIME real (`finfo`), tamaño máximo (5MB) y generación de nombres de archivo aleatorios (`random_bytes`) para evitar sobrescritura o ejecución de archivos maliciosos. |

## 7. Funcionalidades principales

### Módulo administrativo
- Login con bloqueo de cuenta, CSRF y registro de intentos.
- CRUD de usuarios (crear, listar, editar, activar/desactivar), con buscador por nombre/correo.
- CRUD de categorías.
- CRUD de noticias con subida de mínimo 3 imágenes (miniatura + imagen grande generadas automáticamente con GD), firma digital de integridad, estado de publicación y baja lógica.
- Listado de noticias paginado (5 por página) con filtros por categoría, fecha, estado y buscador por título/autor.
- Detalle de noticia con gestión de comentarios (responder, bloquear, eliminar) y verificación de integridad.
- Panel de estadísticas de reacciones por noticia.
- Gestión general de comentarios con filtros por estado.

### Módulo público
- Portada con la noticia más reciente destacada y dos noticias secundarias.
- Listado completo paginado, con filtro por categoría y buscador por título.
- Detalle de noticia con galería de imágenes, botón de "Me gusta" (una reacción por IP) y comentarios (con moderación previa del administrador).
- Contador de visitantes (deduplicado por sesión).
- Diseño responsivo con menú horizontal y enlace a "Inicio" desde cualquier módulo.

## 8. Estructura de la base de datos

Ver `backup.sql` para el script completo y `docs/Informe_Arquitectura_Diseno.md` para el diccionario de datos detallado.

Tablas: `usuarios`, `categorias`, `noticias`, `noticia_imagenes`, `comentarios`, `reacciones`, `login_logs`, `visitas`.

## 9. Documentación adicional

- [`docs/Informe_Arquitectura_Diseno.md`](docs/Informe_Arquitectura_Diseno.md): requisitos funcionales y no funcionales (RNF-01 a RNF-06), diccionario de datos.
- [`docs/Diagramas_UML.md`](docs/Diagramas_UML.md): diagrama de casos de uso, de clases, de secuencia (login), de estados (cuenta de usuario) y entidad-relación (DER), en formato Mermaid.

## 10. Notas técnicas

- El proyecto no utiliza frameworks ni dependencias externas (Composer/Node): es PHP nativo puro, tal como lo exige el enunciado.
- Las imágenes de demostración incluidas en `public/uploads/` son *placeholders* generados automáticamente para que el sistema se vea funcional desde el primer momento; pueden reemplazarse subiendo noticias nuevas desde el panel administrativo.
- El archivo `logs/error.log` registra errores no controlados de la aplicación (se crea automáticamente en el primer error).
