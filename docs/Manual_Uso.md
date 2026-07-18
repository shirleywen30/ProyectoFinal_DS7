# Manual de uso — Sistema de Noticias

Manual breve de instalación y funcionalidades. Para el detalle de arquitectura y diseño, ver [Informe_Arquitectura_Diseno.md](Informe_Arquitectura_Diseno.md), [Diagramas_UML.md](Diagramas_UML.md) y [Modelo_Dominio_IFML.md](Modelo_Dominio_IFML.md).

## 1. Instalación rápida

1. Copiar el proyecto dentro de la carpeta `www` de WAMP (o el `document root` de su servidor PHP/MySQL).
2. Importar `backup.sql` completo en MySQL/MariaDB (crea la base `sistema_noticias`, sus tablas y datos de prueba):
   ```
   mysql -u root -p < backup.sql
   ```
   o desde phpMyAdmin: pestaña *SQL* o *Importar* → pegar/seleccionar `backup.sql`.
3. Revisar `config/database.php` si sus credenciales de MySQL difieren de las de WAMP por defecto (`root` sin contraseña).
4. Abrir `index.php` desde el navegador (sitio público) o `views/admin/login.php` (panel administrativo).

**Usuarios de prueba** (ya incluidos en `backup.sql`):
| Usuario | Contraseña | Rol |
|---|---|---|
| `admin@hotmail.com` | `admin123` | admin |
| `supervisor@hotmail.com` | `super123` | supervisor |
| `editor@hotmail.com` | `editor123` | editor |

## 2. Roles y permisos

| Acción | Admin | Supervisor | Editor |
|---|:---:|:---:|:---:|
| Gestionar usuarios | ✅ | ❌ | ❌ |
| Gestionar categorías | ✅ | ✅ | ❌ |
| Crear noticias | ✅ | ✅ | ✅ |
| Editar cualquier noticia | ✅ | ✅ | ❌ (solo las propias) |
| Publicar/despublicar noticias | ✅ | ✅ | ❌ |
| Aprobar/bloquear/eliminar comentarios | ✅ | ✅ | ❌ |
| Responder comentarios | ✅ | ❌ | ❌ |

Un editor puede crear y modificar noticias, pero estas quedan sin publicar hasta que un supervisor o administrador las publique; tampoco puede editar noticias creadas por otro usuario.

## 3. Funcionalidades principales

- **Noticias**: creación/edición con título, contenido, autor visible (opcional), categoría, estado de publicación (según rol), mínimo 3 imágenes y un video opcional (enlace de YouTube o Vimeo, se embebe automáticamente).
- **Imagen de portada**: la primera imagen cargada se muestra como banner destacado en el detalle público de la noticia; el resto aparece en la galería.
- **Categorías**: Deporte, Eventos, Tecnología, Política y Cultura, cada una con al menos 3 noticias de ejemplo.
- **Comentarios**: los visitantes comentan y quedan en estado "pendiente" hasta que un administrador o supervisor los aprueba o bloquea; solo el administrador redacta la respuesta oficial.
- **Reacciones**: 3 tipos con icono (❤ Me gusta, 🌱 Ecológica, 💡 Interesante), limitado a una reacción por IP y noticia.
- **Búsqueda**: por título, autor o nombre del creador, insensible a mayúsculas/minúsculas (buscar "mundial" o "MUNDIAL" da el mismo resultado).
- **Estadísticas (dashboard admin)**: noticias, usuarios, comentarios pendientes y visitas, filtrables por período (últimos 7 días, 1 mes, 3 meses, 6 meses, 1 año o todo el tiempo).
- **Integridad de contenido**: cada noticia se firma digitalmente (HMAC-SHA256, vía el contrato `HashServiceInterface`) al guardarse; el panel admin verifica esa firma para detectar alteraciones manuales de la base de datos.
- **Página "Nosotros"**: información institucional del proyecto (tecnologías, valor, beneficios, contacto), accesible desde el menú público.

## 4. Estructura del proyecto

- `core/` — clases base (modelo, controlador, base de datos, seguridad, validación, subida de imágenes, servicios de hashing).
- `core/interfaces/` — contratos (`CrudInterface`, `ErrorHandlerInterface`, `HashServiceInterface`).
- `models/` — acceso a datos por tabla (noticias, usuarios, categorías, comentarios, reacciones, visitas, logs).
- `controllers/` — lógica de cada módulo (noticias, público, autenticación, comentarios, reacciones, usuarios, categorías).
- `views/admin/` y `views/public/` — vistas del panel administrativo y del sitio público.
- `public/` — CSS, JS y archivos subidos (imágenes de noticias).
- `config/` — configuración de base de datos y constantes globales.
- `docs/` — documentación técnica del proyecto.

## 5. Créditos

Proyecto académico desarrollado por Jose Barahona, Luis Jiménez, Brian Lee y Shirley Wen — Universidad Tecnológica de Panamá, Licenciatura en Desarrollo y Gestión de Software.
