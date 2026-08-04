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

Esta sección es para los **miembros del equipo técnico** que trabajan directamente con el código.

### ⚡ Inicio rápido (Quick Path)

1. **Clonar e instalar dependencias**:
   ```bash
   git clone <url-del-repositorio>
   cd pit-o-cuixa
   cp .env.example .env
   ```
2. **Inicializar base de datos y sincronizar estilos CSS**:
   ```bash
   php scripts/setup.php       # Crea la base de datos SQLite y tablas iniciales
   php scripts/sync-css.php    # Sincroniza estilos desde src/frontend/css → public/css
   ```
3. **Arrancar servidor local**:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
   Abre `http://localhost:8000` en tu navegador.

---

### 🎨 Arquitectura de CSS y `scripts/sync-css.php`

En este proyecto, **los archivos CSS fuente no se modifican directamente en `public/`**.

| Aspecto | `src/frontend/css/` (Fuente) | `public/css/` (Público) |
|---------|------------------------------|-------------------------|
| **Propósito** | Código fuente mantenido por desarrolladores | Archivos estáticos servidos al navegador |
| **Acceso Web** | Privado (fuera de la raíz del servidor web) | Público (Document Root servido por Nginx/Apache) |
| **Modificación** | **Editar SIEMPRE aquí** | Nunca editar directamente (se sobrescribe) |

#### ¿Por qué usamos `-css.php`?
php scripts/sync
1. **Seguridad y Separación de Arquitectura (Clean Architecture)**:
   El servidor web solo tiene acceso a la carpeta `public/`. Las tripas del backend (`src/`) y la base de datos están fuera de la raíz web para prevenir la exposición accidental de código fuente.

2. **Cero Dependencias (Sin Node/NPM/Webpack)**:
   En lugar de requerir `npm install`, Node.js o herramientas de compilación pesadas, `sync-css.php` es un sincronizador ultra-rápido nativo en PHP que compara marcas de tiempo (`filemtime`) y copia únicamente los archivos modificados desde `src/frontend/css/` hacia `public/css/`.

3. **Control de Versiones de Assets (Cache Buster)**:
   Al compilar/sincronizar los estilos a la carpeta pública, las vistas inyectan `?v=X.Y.Z` para garantizar que los navegadores invaliden la caché HTTP y muestren los cambios al instante sin necesidad de forzar refrescos manuales.

> 💡 **Regla de Oro**: Tras crear o editar cualquier archivo CSS en `src/frontend/css/`, ejecuta siempre:
> ```bash
> php scripts/sync-css.php
> ```

---

### 🛠️ Scripts CLI de automatización

El proyecto cuenta con 4 scripts PHP nativos en `scripts/` para tareas administrativas y de mantenimiento:

| Script | Propósito / Función | Ejemplo de uso |
|--------|---------------------|----------------|
| `scripts/setup.php` | Inicializa la base de datos SQLite (`data/pitocuixa.db`), aplica `db/schema.sql`, inserta datos semilla y crea el usuario administrador inicial. Admite banderas CLI para automatización. | `php scripts/setup.php --fresh --scrape --translate` |
| `scripts/migrate.php` | Ejecuta migraciones de base de datos pendientes desde `db/migrations/*.sql` y registra su ejecución en la tabla `_migrations`. | `php scripts/migrate.php` |
| `scripts/sync-css.php` | Copia y sincroniza hojas de estilo desde `src/frontend/css/` a `public/css/` para que el servidor web sirva la versión más reciente. | `php scripts/sync-css.php` |
| `scripts/translate.php` | Traduce automáticamente en lote categorías y productos a catalán, inglés y ucraniano utilizando la API de DeepL (requiere `DEEPL_API_KEY` en `.env`). | `php scripts/translate.php` |

#### ⚙️ Opciones del script `setup.php`:

| Bandera | Alias | Descripción |
|---------|-------|-------------|
| `--fresh` | `-f` | Elimina la base de datos SQLite existente antes de volver a creárla desde cero. |
| `--scrape` | `-s` | Ejecuta el web scraper tras el setup para poblar/sincronizar los productos desde la carta externa. |
| `--translate` | `-t` | Ejecuta el traductor DeepL tras el setup para traducir campos faltantes (requiere `DEEPL_API_KEY`). |
| `--help` | `-h` | Muestra la ayuda de opciones por consola. |

> 💡 **Ejemplo de reinicio completo para desarrollo**:
> ```bash
> php scripts/setup.php --fresh --scrape --translate
> ```

---

### Configuración de Entorno

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
├── public/           # Raíz web pública — el servidor web apunta exclusivamente aquí
│   ├── css/          # Estilos generados (sincronizados desde src/frontend/css)
│   └── js/           # Scripts JS públicos (ES Modules)
├── src/
│   ├── backend/      # Lógica del servidor: API controllers, Repositorios PDO SQLite
│   ├── frontend/     # Fuentes CSS, plantillas PHP (SSR), maquetación
│   └── shared/       # Configuración global, helper i18n
├── db/               # Migraciones SQL (001, 002, 003, 004) y esquema base
├── data/             # Base de datos SQLite activa (creada por setup.php)
├── scripts/          # Automatización: setup.php, sync-css.php, migrate.php, translate.php
└── openspec/         # Especificaciones técnicas de desarrollo
```

### Stack técnico

| Componente | Tecnología |
|------------|-----------|
| **Lenguaje** | PHP 8.2+ (`strict_types=1`) |
| **Frontend** | HTML5 semántico + CSS Vanilla (Design Tokens) + JavaScript (ES Modules) |
| **Base de datos** | SQLite con WAL (Write-Ahead Logging) |
| **Servidor** | Apache con `mod_rewrite` o PHP built-in server |
| **Estilos** | Design System con tokens CSS, BEM y Responsive Mobile-First |
| **Sincronizador CSS**| Script PHP nativo (`scripts/sync-css.php`) |
| **Plantillas** | SSR con PHP embebido en componentes HTML |
| **Traducciones** | Arrays PHP en `src/shared/i18n/{es,en,ca}.php` + helper `__()` |
| **CI** | GitHub Actions: `php -l` syntax check en cada PR |
| **Dependencias** | Cero dependencias externas (sin Composer ni Node) |

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

### 🚀 Despliegue en Producción (Dinahosting)

En **Dinahosting**, el servidor web (Apache + Varnish / Nginx) utiliza por defecto la carpeta `httpdocs/` como raíz del sitio.

#### 📋 Pasos paso a paso para Dinahosting

1. **Configurar la carpeta raíz (*Document Root*)**:
   - Entra al panel de Dinahosting → **Hosting** → **Ajustes Web** → **Carpeta raíz**.
   - Cambia la carpeta de inicio de `httpdocs` a `httpdocs/public` (o apunta a la carpeta `/public` de tu proyecto).
   - > ⚠️ **CRÍTICO DE SEGURIDAD**: La carpeta `src/`, `.env` y `data/` NUNCA deben ser accesibles directamente vía URL.

2. **Seleccionar Versión de PHP**:
   - En Dinahosting → **Hosting** → **Servidores/Servicios** → **PHP**.
   - Selecciona **PHP 8.2** o superior y confirma que la extensión `pdo_sqlite` esté habilitada.

3. **Subir el código y configurar `.env`**:
   - Sube el repositorio mediante Git (`git pull`) o SFTP.
   - Copia `.env.example` como `.env` en la raíz del servidor y ajusta:
     ```env
     APP_ENV=prod
     SITE_URL=https://pitocuixa.es
     DB_PATH=/datos/data/pitocuixa.db
     ```

4. **Inicializar/Migrar base de datos y sincronizar CSS**:
   - Accede por SSH a Dinahosting y ejecuta:
     ```bash
     php scripts/migrate.php   # Ejecuta las migraciones pendientes en la BD
     php scripts/sync-css.php  # Sincroniza los estilos fuente a public/css/
     ```

5. **Permisos de escritura**:
   - Asigna permisos `775` a las carpetas `data/` (para la base de datos SQLite) y `public/uploads/products/` (para imágenes del catálogo).

---

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
