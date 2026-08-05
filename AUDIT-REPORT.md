# 🔍 Auditoría del Código — Pit o Cuixa

**Fecha:** 5 de agosto de 2026  
**Alcance:** Análisis completo del código fuente (50+ archivos)  
**Objetivo:** Identificar problemas de seguridad, errores en la interfaz, código innecesario y oportunidades de mejora

---

## 📊 Resumen Rápido

| Tipo de Problema | Crítico | Alto | Medio | Bajo |
|------------------|---------|------|-------|------|
| 🔒 Seguridad | 2 | 4 | 5 | — |
| 🎨 Interfaz (UI) | — | 3 | 4 | — |
| 🗑️ Código muerto | — | — | — | 6 |
| 💡 Mejoras | — | — | — | 8 |

**Total de hallazgos:** 32

---

## 🔴 Problemas Críticos de Seguridad

### 1. Cualquiera puede modificar el catálogo de productos

**¿Qué pasa?**  
Hay dos direcciones web (`/api/scraper` y `/api/update-menu`) que están abiertas al público sin protección. Cualquier persona que las conozca puede:
- Ejecutar el scraper (consumir recursos del servidor)
- **Reescribir todo el catálogo de productos** (borrar o cambiar precios, nombres, descripciones)

**¿Por qué es grave?**  
No hay autenticación ni verificación. Es como dejar la puerta del almacén abierta.

**¿Cómo se arregla?**  
- Proteger estas rutas con contraseña/token de administrador
- Cambiar `/api/update-menu` de GET a POST (las operaciones que modifican datos no deben hacerse con GET)
- O eliminarlas si solo se usan en desarrollo

---

### 2. Clave de API de DeepL expuesta

**¿Qué pasa?**  
La clave de API de DeepL está guardada en texto plano en el archivo `.env`.

**¿Por qué es grave?**  
Si alguien accede al servidor, puede robar la clave y usarla (gastando tu crédito de traducción).

**¿Cómo se arregla?**  
- Rotar la clave actual (generar una nueva)
- En producción, usar variables de entorno del servidor en vez de un archivo `.env`

---

## 🟠 Problemas Altos de Seguridad

### 3. Operación de modificación usa el método HTTP incorrecto

**¿Qué pasa?**  
`/api/update-menu` usa GET para modificar datos.

**¿Por qué es grave?**  
Los navegadores, crawlers y proxies pueden ejecutar esta ruta accidentalmente (por ejemplo, al pre-cargar la página).

**¿Cómo se arregla?**  
Cambiar a POST y añadir autenticación.

---

### 4. Sin límite de intentos en el contador de clicks

**¿Qué pasa?**  
El endpoint `POST /api/products/{id}/click` no tiene límite de llamadas por IP.

**¿Por qué es grave?**  
Un atacante puede inflar artificialmente los contadores de clicks de cualquier producto.

**¿Cómo se arregla?**  
Añadir rate limiting (por ejemplo, máximo 10 clicks por IP cada minuto).

---

### 5. Posible inyección SQL en el traductor de menú

**¿Qué pasa?**  
El archivo `MenuTranslator.php` construye nombres de columnas SQL usando variables.

**¿Por qué es grave?**  
Aunque actualmente el riesgo es bajo (las variables vienen de código interno), si alguien cambia el método para aceptar input externo, sería una vulnerabilidad de inyección SQL.

**¿Cómo se arregla?**  
Usar una lista blanca (whitelist) de columnas permitidas en vez de construir nombres dinámicamente.

---

### 6. Las sesiones no se cierran al cambiar contraseña

**¿Qué pasa?**  
Cuando un administrador cambia su contraseña, todas las sesiones activas siguen siendo válidas hasta que expiren (8 horas).

**¿Por qué es grave?**  
Si se cambia la contraseña porque se comprometió la cuenta, el atacante puede seguir usando la sesión activa.

**¿Cómo se arregla?**  
Invalidar todas las sesiones del usuario dentro de la función `updatePassword()`.

---

## 🟡 Problemas Medios de Seguridad

### 7. Categorías creadas sin traducción completa

**¿Qué pasa?**  
Cuando se crea una categoría desde el panel admin, solo se guardan los nombres en español e inglés. Falta catalán y ucraniano.

**Impacto:** Los usuarios con idioma catalán o ucraniano ven nombres vacíos.

---

### 8. Inconsistencia en los idiomas soportados

**¿Qué pasa?**  
El sistema dice soportar 4 idiomas (ca, es, en, uk), pero la página de inicio solo genera enlaces para 3 (falta ucraniano).

**Impacto:** Los buscadores (Google) no detectan la versión ucraniana.

---

### 9. Función global en el controlador principal

**¿Qué pasa?**  
La función `renderPage()` en `index.php` es global, no sigue el patrón de clases del resto del proyecto.

**Impacto:** Menos mantenibilidad y consistencia.

---

### 10. Import marcado para borrar

**¿Qué pasa?**  
Línea 20 de `index.php`: `use Pit\Cuixa\Backend\Db\Repositories\Product; #Borrar en Produ`

**Impacto:** Código innecesario en producción.

---

### 11. Etiqueta de cierre en archivo PHP

**¿Qué pasa?**  
`WebScraper.php` tiene `?>` al final.

**Impacto:** Puede causar output accidental si hay espacios en blanco después.

---

## 🔵 Problemas de Interfaz (UI)

### Alto

#### 12. CSS duplicado en el layout principal

**¿Qué pasa?**  
El archivo `default.php` incluye dos veces:
- El CSS de FAQ (líneas 113-115 y 127-129)
- El CSS del menú slider (líneas 119 y 133)

**Impacto:** 4 peticiones HTTP innecesarias en cada carga de página.

---

#### 13. Idioma ucraniano falta en el cliente API

**¿Qué pasa?**  
El archivo `api-client.js` solo permite `['ca', 'es', 'en']`. Falta `'uk'`.

**Impacto:** Los usuarios ucranianos siempre reciben contenido en catalán como fallback.

---

#### 14. Formulario de login falla si JavaScript no carga

**¿Qué pasa?**  
El formulario de login tiene `action="/api/auth/login"`. Si JavaScript falla, el formulario intenta enviar datos en un formato que el servidor no acepta.

**Impacto:** El usuario ve un error "Invalid JSON body" en vez de un mensaje claro.

---

### Medio

#### 15. Textos en español hardcoded en el panel admin

**¿Qué pasa?**  
El archivo `admin.js` tiene textos como `'Cerrar'`, `'Cancelar'`, `'Eliminar'`, `'Cargando...'` en español.

**Impacto:** El panel admin no se puede traducir a otros idiomas.

---

#### 16. Atributo duplicado en la página de menú

**¿Qué pasa?**  
El atributo `data-menu-products` aparece tanto en la vista "comer en el local" como en "delivery".

**Impacto:** El filtro de productos podría aplicar cambios al canal equivocado.

---

#### 17. Página de inicio no incluye ucraniano en los enlaces de idiomas

**¿Qué pasa?**  
`Home.php` solo genera hreflang para 3 idiomas.

**Impacto:** Los buscadores no detectan la versión ucraniana.

---

#### 18. Script de logout se ejecuta innecesariamente

**¿Qué pasa?**  
`admin.js` intercepta formularios de logout en cada página admin, aunque no exista uno.

**Impacto:** Código que se ejecuta sin necesidad.

---

## 🗑️ Código Muerto (para revisar o eliminar)

### 19. Clase `UpdateMenu.php` nunca usada

**¿Qué pasa?**  
Existe la clase con su método `update()`, pero el router usa código inline en vez de instanciarla.

**Recomendación:** Eliminar la clase o integrarla en el router.

---

### 20. Import marcado `#Borrar en Produ`

**¿Qué pasa?**  
Línea 20 de `index.php` tiene un comentario que dice "Borrar en Produ".

**Recomendación:** Eliminar.

---

### 21. Bloques de código comentados

**¿Qué pasa?**  
`Product.php` líneas 703-717 tiene 3 bloques `/* ... */` comentados para `name_ca`, `description_en`, `description_ca`.

**Recomendación:** Eliminar los comentarios o documentar por qué están desactivados.

---

### 22. Etiqueta de cierre `?>` en `WebScraper.php`

**¿Qué pasa?**  
Línea 153 de `WebScraper.php`.

**Recomendación:** Eliminar (PSR-12 recomienda omitir el closing tag en archivos PHP puros).

---

### 23. CSS duplicado en el layout

**¿Qué pasa?**  
Líneas 127-134 de `default.php` incluyen CSS que ya está en líneas anteriores.

**Recomendación:** Eliminar las duplicadas.

---

### 24. Ruta `/api/scraper` posiblemente obsoleta

**¿Qué pasa?**  
Endpoint público que devuelve el scrape crudo. No parece tener consumidor en el frontend.

**Recomendación:** Verificar si se usa; si no, eliminar.

---

## 💡 Mejoras Sugeridas

### 25. Añadir backup automático de la base de datos

**¿Qué pasa?**  
No hay backup automático de SQLite.

**Recomendación:** Un cron job que copie `data/pitocuixa.db` periódicamente prevendría pérdida de datos.

---

### 26. Estandarizar el manejo de errores en JavaScript

**¿Qué pasa?**  
Varios `catch(() => {})` silenciosos en `main.js` y `menu-filter.js`.

**Recomendación:** Considerar un logger centralizado para no perder información de errores.

---

### 27. Soportar los 4 idiomas en la gestión de categorías

**¿Qué pasa?**  
`AdminCategories::create()` y `update()` solo manejan `name_es` e `name_en`.

**Recomendación:** Deberían soportar los 4 idiomas (ca, es, en, uk).

---

### 28. Mover `renderPage()` a una clase

**¿Qué pasa?**  
La función global en `index.php` no sigue el patrón de clases del proyecto.

**Recomendación:** Crear una clase `PageRenderer` para consistencia.

---

### 29. Validar el tipo de contenido en endpoints JSON

**¿Qué pasa?**  
Los endpoints API deberían verificar `Content-Type: application/json` en requests POST/PUT.

**Recomendación:** No solo intentar parsear, sino validar explícitamente el header.

---

### 30. Añadir headers de cache para endpoints API públicos

**¿Qué pasa?**  
`GET /api/products`, `GET /api/menu`, `GET /api/categories` no tienen headers de cache.

**Recomendación:** Headers de cache cortos (30-60s) reducirían la carga en SQLite.

---

### 31. Añadir `X-Content-Type-Options` a nivel PHP

**¿Qué pasa?**  
Los headers de seguridad están solo en `.htaccess`.

**Recomendación:** Si Apache no tiene `mod_headers`, las respuestas API no tienen protección. Añadirlos desde PHP.

---

### 32. Query no preparada en `Products::popular()`

**¿Qué pasa?**  
Línea 634 de `Product.php`: `$this->pdo->query('SELECT SUM(clicks_count) FROM products WHERE is_active = 1')`

**Impacto:** Aunque no hay input del usuario, es inconsistente con el resto del código que usa prepared statements.

---

## 📋 Checklist de Prioridades

### 🔴 Acción Inmediata (Crítico)
- [ ] Proteger endpoints `/api/scraper` y `/api/update-menu` con autenticación
- [ ] Rotar la clave de API de DeepL

### 🟠 Acción Urgente (Alto)
- [ ] Cambiar `/api/update-menu` de GET a POST
- [ ] Añadir rate limiting al endpoint de clicks
- [ ] Revisar `MenuTranslator.php` para evitar inyección SQL
- [ ] Invalidar sesiones al cambiar contraseña

### 🟡 Acción Planificada (Medio)
- [ ] Completar traducciones en categorías (ca, uk)
- [ ] Añadir ucraniano a los hreflang de la home
- [ ] Eliminar CSS duplicado en `default.php`
- [ ] Añadir ucraniano a `api-client.js`

### 🗑️ Limpieza
- [ ] Eliminar `UpdateMenu.php` si no se usa
- [ ] Eliminar import marcado `#Borrar en Produ`
- [ ] Eliminar bloques comentados en `Product.php`
- [ ] Eliminar closing tag `?>` en `WebScraper.php`

### 💡 Mejoras Futuras
- [ ] Implementar backup automático de SQLite
- [ ] Centralizar manejo de errores JS
- [ ] Soportar 4 idiomas en admin categories
- [ ] Mover `renderPage()` a clase
- [ ] Validar Content-Type en endpoints
- [ ] Añadir headers de cache
- [ ] Añadir `X-Content-Type-Options` desde PHP
- [ ] Usar prepared statement en `Products::popular()`

---

## 📝 Notas Finales

**Puntos fuertes del código:**
- Arquitectura limpia y bien organizada
- Uso consistente de prepared statements (excepto 1 caso)
- Protección CSRF en el panel admin
- Rate limiting en login
- Separación clara entre backend, frontend y shared

**Áreas de mejora principales:**
- Seguridad de endpoints públicos (CRÍTICO)
- Completar el soporte multilingüe (ucraniano falta en varios lugares)
- Eliminar código muerto y duplicado
- Mejorar manejo de errores

---

**Documento generado por auditoría automatizada**  
**Revisado:** 5 de agosto de 2026
