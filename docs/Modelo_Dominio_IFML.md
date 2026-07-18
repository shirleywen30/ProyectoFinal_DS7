# Modelo de Dominio (UML) y Modelado de Interacción (IFML)

Este documento complementa a [Diagramas_UML.md](Diagramas_UML.md) (que modela las clases de *software*: controladores, modelos y clases de infraestructura) con dos vistas de más alto nivel:

1. Un **Modelo de Dominio UML**, centrado únicamente en las entidades de negocio y sus relaciones (sin métodos ni clases técnicas).
2. **Diagramas de Modelado de Interacción (IFML)**, que describen las pantallas (*ViewContainers*), sus contenidos y los flujos de navegación entre ellas.

> Nota de notación: Mermaid no tiene soporte nativo para la notación gráfica formal de IFML (rectángulos redondeados para ViewContainer, iconos de formulario para Form, flechas de InteractionFlow, etc.). Los diagramas siguientes son una representación equivalente simplificada usando `flowchart`, con la siguiente leyenda:
>
> | Notación IFML real | Representación aquí |
> |---|---|
> | ViewContainer (pantalla) | Rectángulo `[Nombre]` |
> | View / List (contenido) | Sub-nodo dentro del rectángulo, listado en el texto |
> | Event (clic, submit) | Etiqueta sobre la flecha |
> | InteractionFlow (navegación) | Flecha `-->` |

## 1. Modelo de Dominio (UML)

Entidades de negocio únicamente (sin `id_usuario_admin` de auditoría técnica ni columnas de control como `firma_digital`, ya detalladas en el diccionario de datos de `Informe_Arquitectura_Diseno.md`):

```mermaid
classDiagram
    class Usuario {
        +nombre
        +email
        +rol : admin|editor|supervisor
        +activo
    }
    class Categoria {
        +nombre
        +descripcion
        +activo
    }
    class Noticia {
        +titulo
        +contenido
        +autor
        +videoUrl
        +publicado
        +activo
        +fechaCreacion
    }
    class ImagenNoticia {
        +rutaImagen
        +rutaThumbnail
        +orden
    }
    class Comentario {
        +nombreUsuario
        +email
        +comentario
        +estado : pendiente|aprobado|bloqueado
        +respuesta
    }
    class Reaccion {
        +tipo : like|eco|interesante
        +ipAddress
    }
    class Visita {
        +sessionId
        +ipAddress
        +fecha
    }

    Usuario "1" --> "0..*" Noticia : crea
    Usuario "0..1" --> "0..*" Comentario : responde
    Categoria "1" --> "0..*" Noticia : clasifica
    Noticia "1" --> "3..*" ImagenNoticia : contiene
    Noticia "1" --> "0..*" Comentario : recibe
    Noticia "1" --> "0..*" Reaccion : recibe
```

Reglas de negocio relevantes del dominio (no representables solo con el diagrama):
- Toda noticia requiere un mínimo de 3 imágenes.
- Un editor solo puede crear/modificar noticias donde `Noticia.usuario = Usuario` en sesión; un supervisor o admin puede hacerlo sobre cualquier noticia.
- Solo admin/supervisor pueden marcar `Noticia.publicado = true`.
- Una `Reaccion` es única por combinación de noticia + IP (de cualquier tipo).
- Un `Comentario` público nace en estado `pendiente` y requiere aprobación.

## 2. IFML — Flujo del sitio público

```mermaid
flowchart TD
    Home["[Home]\n- Noticia destacada\n- 2 noticias secundarias\n- Contador de visitas"]
    Todas["[Todas las noticias]\n- Filtro por categoría/búsqueda\n- Listado paginado"]
    Detalle["[Detalle de noticia]\n- Banner + galería + video\n- Reacciones con iconos\n- Comentarios aprobados\n- Formulario de comentario"]
    Nosotros["[Nosotros]\n- Info institucional\n- Contactos"]
    Login["[Login admin]"]

    Home -- "clic 'Ver todas'" --> Todas
    Home -- "clic en noticia" --> Detalle
    Todas -- "clic en noticia" --> Detalle
    Detalle -- "submit comentario" --> Detalle
    Detalle -- "clic reacción" --> Detalle
    Home -- "clic 'Nosotros'" --> Nosotros
    Home -- "clic 'Iniciar sesión'" --> Login
```

## 3. IFML — Flujo del panel administrativo (con permisos por rol)

```mermaid
flowchart TD
    Login["[Login]"]
    Dashboard["[Dashboard]\n- Estadísticas filtrables por período"]
    NoticiasList["[Noticias · listar]\n- Buscar/filtrar\n- Editar: propias (editor) o todas (supervisor/admin)\n- Publicar: solo supervisor/admin"]
    NoticiaForm["[Noticia · crear/editar]\n- Imágenes + video\n- Estado de publicación bloqueado para editor"]
    NoticiaDetalle["[Noticia · detalle]\n- Moderar comentarios: admin/supervisor\n- Responder comentario: solo admin"]
    Comentarios["[Comentarios · listar]\n(solo admin/supervisor)"]
    Categorias["[Categorías · CRUD]\n(solo admin/supervisor)"]
    Usuarios["[Usuarios · CRUD]\n(solo admin)"]

    Login -- "credenciales válidas" --> Dashboard
    Dashboard -- "clic 'Noticias'" --> NoticiasList
    NoticiasList -- "clic 'Nueva'/'Editar'" --> NoticiaForm
    NoticiaForm -- "guardar" --> NoticiasList
    NoticiasList -- "clic 'Ver'" --> NoticiaDetalle
    NoticiaDetalle -- "responder (solo admin)" --> NoticiaDetalle
    Dashboard -- "clic 'Comentarios' (admin/supervisor)" --> Comentarios
    Dashboard -- "clic 'Categorías' (admin/supervisor)" --> Categorias
    Dashboard -- "clic 'Usuarios' (solo admin)" --> Usuarios
```

## 4. Relación con los requisitos funcionales

Estos diagramas formalizan visualmente los flujos ya descritos como requisitos funcionales (RF) en `Informe_Arquitectura_Diseno.md` y el control de acceso por rol implementado en `core/BaseController.php::requireRole()`, `controllers/NewsController.php` (`isPrivileged()`/`canModify()`) y `controllers/CommentController.php` (`requireModerator()`).
