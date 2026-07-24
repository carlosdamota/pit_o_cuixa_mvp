# 🍗 Pit o Cuixa — Web

**Pit o Cuixa** es la web oficial de la pollería ubicada en Torredembarra (Tarragona). Un equipo de **8 personas** trabaja en este repositorio para ofrecer una carta online, un panel de administración y una experiencia que funciona incluso sin conexión.

👨‍🍳 **Equipo del restaurante** · 👩‍💻 **Personas técnicas**

> ### ✅ Cómo empezar en 30 segundos
> - **Si formas parte del equipo del restaurante**: abre `https://pitocuixa.es/admin` e inicia sesión. Si no tienes usuario y contraseña, pídele a una persona técnica que te los dé.
> - **Si eres desarrollador**: clona el repositorio, copia `.env.example` como `.env`, ejecuta `php scripts/setup.php` y luego `php -S 0.0.0.0:8000 -t public`.

---

## 👨‍🍳 Guía para el equipo del restaurante

Esta sección es para **los 4 miembros del equipo que no trabajan directamente con el código**. Aquí encontrarás todo lo que necesitas para usar la web a diario.

### Qué es esta web

Es la página oficial de **Pit o Cuixa**. Sirve para que los clientes vean la carta online y para que el equipo gestione los productos, los precios y las categorías desde un panel privado.

### Cómo entrar al panel de administración

| Dato | Valor |
|------|-------|
| **Dirección** | `https://pitocuixa.es/admin` |
| **Usuario** | El que te haya dado el equipo técnico (normalmente `admin`) |
| **Contraseña** | Pídesela a una persona técnica. No la compartas. |

1. Abre `https://pitocuixa.es/admin` en el navegador.
2. Escribe tu usuario y contraseña.
3. Haz clic en **Iniciar sesión**.
4. Ya estás dentro. Verás un menú con **Productos** y **Categorías**.

### Cómo gestionar productos

- **Añadir**: ve a **Productos** → **Añadir producto**, rellena los campos y guarda.
- **Modificar**: busca el producto, haz clic en **Editar**, cambia lo necesario y guarda.
- **Eliminar**: busca el producto, haz clic en **Eliminar** y confirma.

### Cómo gestionar categorías

Funciona igual que los productos: desde el menú **Categorías** puedes añadir, modificar o eliminar categorías (Pollo, Menú diario, Postre, etc.).

### Cómo importar o exportar el catálogo

- **Exportar**: desde el panel, haz clic en **Exportar CSV**. Se descargará un archivo con todos los productos.
- **Importar**: prepara un archivo CSV con el mismo formato y haz clic en **Importar CSV** en el panel.

### Qué hacer si algo falla

| Problema | Solución |
|----------|----------|
| No recuerdo mi contraseña | Pídele a una persona técnica que la restablezca. |
| La web no carga | Comprueba tu conexión. Si sigue sin funcionar, avisa al equipo técnico. |
| Un producto no se ve en la carta | Revisa que esté guardado correctamente. Si lo está, avisa al equipo técnico. |

Si el problema persiste, pídele ayuda a una persona técnica.

---

## 👩‍💻 Guía para personas técnicas

Esta sección es para los **4 miembros con perfil técnico** que trabajan directamente con el código.

### Requisitos previos

- **PHP 8.2** o superior (con extensión `pdo_sqlite`)
- **SQLite 3** (incluido con PHP)
- **Git**

### Instalación

```bash
git clone <url-del-repositorio>
cd pit-o-cuixa
cp .env.example .env
# Ajusta los valores en .env (ver tabla de configuración)
php scripts/setup.php
php -S 0.0.0.0:8000 -t public
```

Abre `http://localhost:8000` y ya deberías ver la web funcionando.

### Configuración

| Variable | Por defecto | Significado |
|----------|-------------|-------------|
| `APP_ENV` | `prod` | Entorno: `dev`, `prod` o `test` |
| `DB_PATH` | `./data/pitocuixa.db` | Ruta al archivo de la base de datos |
| `SITE_URL` | `https://pitocuixa.es` | URL pública del sitio |
| `SESSION_LIFETIME` | `28800` | Duración de la sesión en segundos (8h) |
| `DEFAULT_LOCALE` | `es` | Idioma por defecto: `es` o `en` |

### Estructura del proyecto

```
pit-o-cuixa/
├── public/           # Raíz web — el servidor apunta aquí
├── src/
│   ├── backend/      # Lógica del servidor: API, admin, BD
│   ├── frontend/     # Plantillas, CSS, JS
│   └── shared/       # Configuración, traducciones (ES/EN)
├── db/               # Esquema SQL y datos iniciales
├── data/             # Base de datos SQLite (se crea con setup.php)
├── scripts/          # Herramientas: setup.php, import/export CSV
└── openspec/         # Documentación técnica
```

### Stack técnico

| Componente | Tecnología |
|------------|-----------|
| **Lenguaje** | PHP 8.x (`strict_types=1`) |
| **Frontend** | HTML + CSS vanilla + JavaScript (ES Modules) |
| **Base de datos** | SQLite con WAL |
| **Servidor** | Apache con `mod_rewrite` o PHP built-in server |
| **Estilos** | Design System con variables CSS (tokens), BEM |
| **CSS** | `src/frontend/css/` → servido desde `public/css/` |
| **JS** | `src/frontend/js/` → servido desde `public/js/` (ES Modules) |
| **Plantillas** | PHP embebido en HTML (SSR) |
| **Traducciones** | Arrays PHP en `src/shared/i18n/{es,en}.php` + helper `__()` |
| **CI** | GitHub Actions: `php -l` syntax check en cada PR |
| **Dependencias** | Ninguna — PHP vanilla, sin frameworks |

### Arquitectura

```
public/index.php (front controller — única entrada)
 │
 ├─ /api/* → src/backend/api/ → PDO → SQLite (JSON)
 ├─ /admin/* → src/backend/pages/admin/ → renderiza templates (HTML)
 └─ /* → src/backend/pages/ → src/frontend/templates/* (HTML+PHP)
         └── layout/default.php envuelve cada página
```

### Rutas principales

| Ruta | Método | ¿Qué hace? | Tipo |
|------|--------|------------|------|
| `/` | GET | Página principal (hero, destacados) | HTML |
| `/menu` | GET | Carta completa agrupada por categorías | HTML |
| `/admin` | GET | Panel de administración | HTML |
| `/admin/login` | GET | Formulario de inicio de sesión | HTML |
| `/admin/products` | GET | CRUD de productos | HTML |
| `/admin/categories` | GET | CRUD de categorías | HTML |
| `/api/products` | GET | Lista de productos | JSON |
| `/api/categories` | GET | Todas las categorías | JSON |
| `/api/menu` | GET | Carta completa agrupada | JSON |
| `/api/auth/login` | POST | Inicio de sesión admin | JSON |
| `/api/auth/logout` | POST | Cierre de sesión | JSON |
| `/api/admin/products` | POST/PUT/DELETE | CRUD productos (requiere token) | JSON |
| `/api/admin/categories` | POST/PUT/DELETE | CRUD categorías (requiere token) | JSON |
| `/api/admin/import` | POST | Importar CSV de productos | JSON |
| `/api/admin/export` | GET | Exportar CSV de productos | CSV |
| `/sitemap.xml` | GET | Sitemap dinámico con hreflang | XML |
| `/robots.txt` | GET | Robots dinámico | Texto |

### Autenticación

El panel de administración usa **tokens en base de datos** (no sesiones PHP nativas). El login devuelve un token que se envía como `Authorization: Bearer <token>` en las llamadas API. Las páginas HTML de admin usan una cookie `httpOnly` + `SameSite=Lax`.

### Base de datos

SQLite con PDO y WAL. 5 tablas:

| Tabla | Contenido |
|-------|-----------|
| `categories` | ~11 categorías (Pollo, Menú diario, Postre, etc.) |
| `products` | ~45 productos con nombre ES/EN, precio, imagen |
| `users` | Usuarios administradores |
| `sessions` | Tokens de sesión activos |
| `settings` | Configuración clave-valor |

```bash
php scripts/setup.php    # Crear la base de datos desde cero
```

### Design System

| Variable | Valor | Uso |
|----------|-------|-----|
| `--color-primary` | `#f7e721` (amarillo) | Bloques hero, CTAs, acentos |
| `--color-secondary` | `#d32f2f` (rojo) | Precios, ofertas, badges |
| `--color-surface` | `#f7f9ff` | Fondos de sección |
| `--font-family` | Quicksand | Tipografía principal |
| `--radius` | `8px` | Esquinas redondeadas |

Tokens en `src/frontend/css/tokens.css`. Metodología **BEM** para clases.

### PWA

| Recurso | ¿Qué hace? |
|---------|------------|
| `public/manifest.json` | Configuración de instalación (iconos 192/512) |
| `public/sw.js` | Service worker: 4 estrategias de caché |
| `public/offline.html` | Página de respaldo sin conexión |
| `public/img/icon-*.svg` | Iconos para la instalación |

### SEO / GEO

Todas las páginas incluyen:
- Meta OG para redes sociales
- JSON-LD (`Restaurant`, `LocalBusiness`, `Menu`)
- Etiquetas `hreflang` (español e inglés)
- Geolocalización (Torredembarra, Tarragona)
- Sitemap XML dinámico

### CI / CD

GitHub Actions ejecuta `php -l` en cada Pull Request y push a `main`.

---

## Equipo y contacto

| Rol | Persona |
|-----|---------|
| **Responsable técnico** | @pitocuixa/tech-lead |
| **Equipo Backend** | @pitocuixa/backend |
| **Equipo Frontend** | @pitocuixa/frontend |

Para dudas o incidencias, abre un issue en GitHub o menciona al equipo en un Pull Request.

---

## Mantenimiento de este README

Este documento es **una guía viva**: se actualiza cuando el proyecto cambia. Revisa que la información siga siendo correcta si:

- [ ] Se añade o modifica una ruta importante
- [ ] Cambia la configuración de `.env`
- [ ] Cambia la estructura de la base de datos
- [ ] Se añade una nueva funcionalidad (PWA, SEO, API, etc.)
- [ ] Cambia el equipo o los roles
- [ ] Se modifica el flujo de CI/CD

Los cambios en este README requieren revisión de **@pitocuixa/tech-lead** mediante Pull Request (protegido por CODEOWNERS).

---

> ⚡ Hecho con dedicación por el equipo de Pit o Cuixa.
> Para más detalles técnicos, consulta las [especificaciones](openspec/specs/) y la [documentación del proyecto](openspec/).
