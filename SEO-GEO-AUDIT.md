# Auditoría SEO · GEO · Posicionamiento en LLM — Pit o Cuixa

> Rama: `feature/seo_geo` · Fecha: 2026-08-27
> Método: auditoría de código (todos los ficheros SEO-relevantes) + sitio en producción (`pitocuixa.es`) + muestreo real de resultados de búsqueda (SERP) y catálogo competitivo.
> Este documento complementa `AUDIT-REPORT.md`; las referencias `fichero:línea` apuntan al código en el momento de la auditoría.

---

## 1. Resumen ejecutivo (veredicto)

| Pregunta | Veredicto |
|---|---|
| ¿La web está técnicamente preparada para SEO? | **Base sólida pero con fallos que la anulan** (og:image 404, portada sin texto indexable, NAP contradictorio, catálogo con basura). |
| ¿Somos visibles hoy en búsquedas locales? | **No.** Para "pollería / rostisseria / pollo asado Torredembarra" la web no aparece; los competidores sí (vía directorios). |
| ¿Somos citables por IAs (ChatGPT, Perplexity, Gemini)? | **Fundamento creado (llms.txt, JSON-LD, FAQPage) pero minado por datos sucios**: productos corruptos en llms.txt, direcciones contradictorias y precios que no cuadran. |
| ¿Qué es lo más urgente? | **La home no da contenido a indexar** y el `llms.txt` publica un catálogo corrupto. Corregir ambas + consolidar NAP es trabajo de días, no de meses. |

---

## 2. Estado actual por capa

### 2.1 SEO clásico

**Lo que ya está bien (mantener):**
- SSR completo: todo el contenido público está en el HTML inicial (menú, FAQ, poblaciones de reparto). ✅
- Hreflang completo y bidireccional (ca/es/en/uk + `x-default`) en layout, controladores y sitemap. ✅
- `FAQPage` JSON-LD con 9 preguntas reales en los 4 idiomas. ✅
- Sitemap dinámico con `xhtml:link` y Content-Type correcto; `robots.txt` con línea `Sitemap:`. ✅
- HSTS, cache headers estáticos, canonical en todas las páginas. ✅

**Fallos críticos:**

1. **`og:image` y `image` del JSON-LD → 404.** El layout referencia `/img/og-image.jpg` (`src/frontend/templates/layouts/default.php:24,50,158`) pero ese fichero **no existe** en `public/img/`. Rompe las previews en redes y debilita la señal de imagen de la entidad.
2. **La home casi no tiene texto indexable.** El único `<h1>` del sitio es *"Clicka per disfrutar"* (`src/frontend/templates/pages/home.php:67`). El copy con keywords ya está escrito en i18n pero **no se renderiza**: `home.hero.title` = *"El millor pollo a l'ast de Torredembarra"* y `home.hero.subtitle` = *"Des de 1998…"* (`src/shared/i18n/ca.php:421-430`) están sin usar. Además el header y footer (donde vive el NAP) están ocultos en la home (`default.php:197-199,207-209`).
3. **NAP contradictorio (grave para SEO local).** JSON-LD, `llms.txt` y el CTA de Maps dicen **"Carrer Hort de l'Oca, 12, 43830"**; el footer/FAQ renderizado dice **"Carrer Major, 25, 43800"** (`home.info.address`, `ca.php:427`). Las coordenadas también discrepan (JSON-LD `41.1413,1.3894` vs geo-meta `41.1412,1.3939`). El teléfono histórico indexado (977 64 18 05, ver §2.4) difiere del actual (+34 977 64 20 10). Confirmación externa: Uber Eats y reseñas de terceros sitúan el local en **Hort de l'Oca 12** → el footer está desactualizado.
4. **Títulos débiles:** `menu.title` "Carta — Pit o Cuixa" y `menu.desc` sin localidad; `faq.title` "Preguntes freqüents" sin marca ni ubicación.
5. **H1 del menú hardcodeado en español** ("Los mejores platos y menús de Pit o Cuixa", `menu.php:109`) — aparece igual en catalán, inglés y ucraniano.
6. **Sitemap sin `lastmod`** (recomendado con catálogo que cambia por scraping cada 12 h) y solo 6 URLs sin páginas de producto.
7. **Sin redirecciones 301 www→non-www ni http→https** en `.htaccess`/`web.config` (HSTS solo protege tras la primera visita HTTPS). Riesgo de indexación de hosts duplicados.
8. **Sin infraestructura de `<meta name="robots">`**: el `'index' => false` del login admin (`Login.php:26`) es código muerto; el layout nunca lo emite.
9. **CSS duplicado en el layout** (`default.php:97-119`): 9 hojas enlazadas dos veces → peticiones desperdiciadas (arrastra CWV).
10. **`og:type` fijo en "website"**, sin `twitter:site`, sin metas de verificación de Search Console/Bing.
11. **`/api/*` públicos rastreables** sin `X-Robots-Tag: noindex` (solo se bloquea `/api/pitocuixa/`).

### 2.2 SEO local

- **NAP en HTML visible:** solo en footer, y **oculto en la home** (punto 2.1.3). No existe página de contacto/ubicación dedicada.
- **Restaurant JSON-LD incompleto** (`default.php:150-182`): faltan `priceRange`, `servesCuisine` (¡estaban en nuestra propia spec `openspec/specs/seo-geo/spec.md:145-148`!), `acceptsReservations`, `hasMap`, `sameAs`, `aggregateRating`/`review`.
- **Entidad fragmentada:** el bloque `FoodEstablishment` del menú (`menu.php:356-397`) no tiene `@id` → segunda entidad sin vincular en vez de reforzar `Restaurant/#business`.
- **Drift de nombres en zonas de reparto:** el mapa/`llms.txt` dicen "La Riera de Gaià"; la FAQ dice "La Nou de Gaià". Son municipios distintos.
- **`manifest.json` con `lang: "es"`** cuando el idioma por defecto del sitio es `ca`.

### 2.3 GEO / LLM (visibilidad en motores generativos)

**Lo bueno:**
- `llms.txt` existe y está bien estructurado (resumen ejecutivo, dirección, GPS, horario, WhatsApp, catálogo por categorías, Q&A para IA). Pionero para una pollería de barrio. ✅
- Crawlers de IA (GPTBot, ClaudeBot, PerplexityBot, Google-Extended, CCBot…) **permitidos por defecto** vía `User-agent: *`. ✅
- `FAQPage` schema + SSR de las 9 respuestas: exactamente lo que un answer engine quiere citar. ✅

**Lo que lo invalida:**

1. **Catálogo corrupto en `llms.txt` (crítico).** El scraper importa basura que sale publicada como producto: **"Estómac" (8,00 €)**, **"BOND" (20,00 €)**, "Fideuà" duplicado a dos precios (7,50 € y 11,00 €), y **gastos de envío listados como productos** con descripciones absurdas (*"Política del restaurant «No tocar»"*, *"«No molestar»"*). Ese mismo catálogo alimenta el `hasMenuSection` del JSON-LD del menú. Una IA que cite "Estómac 8 €" degrada la marca ante el usuario.
2. **`llms.txt` solo en catalán** (resuelve `LANG=ca` para bots sin `?lang=`), sin variantes por idioma.
3. **Precios que no cuadran entre canales:** web `llms.txt` 19,50 € (pollo+truita+alioli) vs Uber Eats 21,90 € (POLLO+PATATAS+ALIOLI). Los LLM contrastan fuentes; la divergencia reduce la confianza en cualquier fuente.
4. **Sin política explícita de crawlers de IA en robots.txt** (hoy por omisión, no por decisión).
5. **Sin URLs citables por plato/categoría** (todo vive dentro de `/menu`), sin `BreadcrumbList` ni `WebSite`/`SearchAction`.
6. **Corpus de entidad insuficiente:** la home vacía, sin página "Quienes somos" (el "des de 1998" solo existe en una clave i18n sin usar), sin reseñas textuales en el sitio, sin blog. Para un answer engine, de Pit o Cuixa hay menos texto fiable que de sus competidores (que viven en directorios con decenas de reseñas).

### 2.4 Realidad del índice (evidencia SERP, agosto 2026)

- **Consulta de marca "Pit o Cuixa" Torredembarra:** el primer resultado ES `pitocuixa.es`, pero con **snippet de la web antigua de WordPress**: título *"PIT O CUIXA ☎️ 977 64 18 05 – Rostiseria/restaurante"* y URLs `wp-content/...`. El buscador sigue indexando la versión anterior: la portada actual, sin texto, no da motivo de re-rastreo con contenido nuevo.
- **Consultas locales no-brand** ("pollería Torredembarra", "rostisseria Torredembarra", "pollastre a l'ast"): **pitocuixa.es no aparece**. Los competidores sí, a través de directorios y agregadores (sluurpy.es, ilmondodelpollo.es, mappesp.com, localitybiz.es, latocineria.es).
- **Terceros que hoy representan mejor la marca que nuestra propia web:** Uber Eats (ficha con carta y precios), Glovo, `ilmondodelpollo.es/pit-o-cuixa/` (reseñas agregadas, 4,5/5), LinkedIn company page.
- **Ruido de entidad:** existe "PIT I CUIXA GOURMET SL / Rostisseria Pit I Cuixa" en Santa Coloma de Gramenet (otro negocio), un libro y un blog llamados "pit i cuixa". Sin `sameAs` ni señales de desambiguación, los motores pueden mezclar identidades.

---

## 3. Panorama competitivo (Torredembarra y entorno)

| Competidor | Ubicación | Fortaleza digital observada |
|---|---|---|
| **Rostisseria Sara** | C. Pere Badia 49 | Muchas reseñas textuales en directorios; muy citada para "pollo a l'ast". |
| **Rostisseria Martí** | Plaça de la Vila 10 | 4,4/5; presencia en múltiples directorios; fama de "encargar con antelación". |
| **The Chicken** | C. Sinibald Mas 20 | Ficha rica en sluurpy con carta y reseñas; delivery + takeaway + local. |
| **El Rincón del Pollo** | Pg. de la Sort 37 | Presencia básica en directorios. |
| **Comida y Pollos Asados Para Llevar** | C. Filadors 10 | Reseñas en ilmondodelpollo. |
| **Rustic** | C. Pere Badia 49 | Artículo-guía extenso y reciente (latocineria.es, 2025) que le regala contenido y keywords. |
| **Pollos a l'ast Tere** | Creixell (limítrofe) | Ficha en carta.menu con Q&A SEO. |
| **El Pollastre Rostit** | Reus (referencia regional) | Web propia antigua pero con texto keyword-rich: "pollo a l'ast", "comida para llevar Reus", listado completo de platos. |

**Lectura:** en esta niche nadie tiene web moderna; la visibilidad local hoy la concentran los **directorios de reseñas**. Eso significa que (a) el pack local de Google + reseñas es la batalla real, y (b) una web SSR con contenido real y datos estructurados correctos puede **superar a todos rápidamente** — el listón orgánico es bajo.

**Diferenciadores de Pit o Cuixa explotables como contenido:**
- **Pollastre Groc Català** (procedencia certificada, Alimentbarna, ISO 9001/22000) — ya está en la FAQ, nadie más lo comunica.
- Reformulación/rostit propio, croquetas artesanas, paella/fideuà para grupos.
- Reparto propio en 6 núcleos: Torredembarra, Altafulla, Creixell, La Móra, La Pobla de Montornès, La (Nou/Riera) de Gaià.
- Presencia en Uber Eats y Glovo (los competidores de barrio no la tienen todas).
- Tradición ("des de 1998") y trato familiar.

---

## 4. Estrategia de keywords

Idiomas por prioridad real del mercado: **catalán** (lengua local de búsqueda, "pollastre a l'ast", "rostisseria", "menjar per emportar"), **castellano** (búsquedas mixtas y turismo nacional, "pollo asado", "comida para llevar"), **inglés** (turismo de playa en verano, "takeaway", "rotisserie"). El ucraniano es de servicio, no de adquisición.

### Clúster 1 — Intención local pura (máximo valor, la batalla principal)

| Keyword | Intención | Prioridad | Dónde atacar |
|---|---|---|---|
| pollería Torredembarra | Local pack + orgánico | P0 | Home (H1/title/desc), GBP, JSON-LD `servesCuisine` |
| pollastre a l'ast Torredembarra | Local, en catalán | P0 | Home + menú + GBP |
| pollo asado Torredembarra | Local | P0 | Home + menú + GBP |
| rostisseria Torredembarra | Local (término local nativo) | P0 | Home/menú + sameAs + GBP |
| mejor pollo asado Torredembarra / "els millors pollastres a l'ast" | Local comparativa | P1 | Sección reseñas/testimonios, contenido "desde 1998" |
| asador de pollos Torredembarra | Local | P1 | FAQ + descripciones de categoría |
| pollería cerca de mí / pollo asado near me | Local-GPS | P0 | GBP + NAP consistente + geo JSON-LD |

### Clúster 2 — Comida para llevar y delivery

| Keyword | Intención | Prioridad | Dónde atacar |
|---|---|---|---|
| comida para llevar Torredembarra | Takeaway | P0 | Menú (title/desc) + GBP |
| menjar per emportar Torredembarra | Takeaway (ca) | P0 | Menú ca |
| pollo asado a domicilio Torredembarra | Delivery | P0 | Zonas de reparto + FAQ + Uber Eats/Glovo sameAs |
| comida a domicilio Torredembarra | Delivery genérica | P1 | FAQ ("¿repartís a…?") |
| menú diario para llevar Torredembarra / menú diari per emportar | Recurrente L-V | P1 | Sección menú diario en /menu + FAQ |
| paella para llevar Torredembarra / fideuà per emportar | Producto | P1 | Categorías del menú con URL citable |
| croquetas caseras Torredembarra | Producto | P2 | Categoría croquetas |

### Clúster 3 — Estacional / playa / turismo (verano, es+en)

| Keyword | Intención | Prioridad | Dónde atacar |
|---|---|---|---|
| comida para llevar playa La Móra | Playa | P1 | Contenido de zonas (La Móra es playa de Torredembarra) |
| takeaway food Torredembarra beach | Turista EN | P2 | Página/faq EN |
| rotisserie chicken Torredembarra | Turista EN | P2 | Home EN |
| comida para llevar Torredembarra playa | Turista ES | P2 | Zonas de reparto |

### Clúster 4 — Marca y entidad

| Keyword | Estado actual | Acción |
|---|---|---|
| pit o cuixa | #1 pero con **snippet obsoleto de la era WordPress** (título y teléfono viejos) | Re-publicar contenido real + solicitar reindexación (Search Console) |
| pit o cuixa carta / menú | Terceros (Uber Eats, Glovo) capturan la intención | URLs citables por categoría + `sameAs` |
| pit o cuixa teléfono | Teléfono viejo indexado (977 64 18 05) | NAP unificado + reindexación |
| pit i cuixa (variante) | Riesgo de confusión con "Pit I Cuixa Gourmet SL" (Santa Coloma) | `sameAs` + nombre consistente en todos los perfiles |

### Clúster 5 — Preguntas conversacionales (GEO/LLM)

Las consultas a IAs son largas y conversacionales: *"¿dónde compro el mejor pollo asado en Torredembarra?"*, *"¿qué pollería reparte a domicilio en Altafulla?"*, *"¿dónde compro paella para llevar cerca de Torredembarra playa?"*. Para ser la respuesta hay que ser **citado**, y para ser citado hace falta: NAP idéntico en todas las fuentes, precios claros, horarios, zonas de reparto explícitas, reseñas textuales y datos estructurados sin contradicciones. Todo lo que rompe eso hoy está listado en §2.3.

---

## 5. ¿Podríamos ser relevantes hoy?

- **En orgánico local (Google clásico): hoy NO.** Sin contenido indexable en la home, sin GBP verificado (a confirmar, es la palanca #1 y es off-site) y con el índice todavía en la versión WordPress, no aparecemos en ninguna consulta local no-brand.
- **En IA/LLM: hoy SOMOS citables pero con datos que nos perjudican.** `llms.txt` + JSON-LD + FAQ es más de lo que tiene cualquier competidor de la zona… pero publicado con "Estómac" y "BOND" en el catálogo, dos direcciones distintas y precios que no cuadran con Uber Eats. Una IA que contraste fuentes hoy tendría razón para desconfiar.
- **La ventaja: el listón es bajísimo.** Los competidores solo existen vía directorios de reseñas. Con las correcciones P0 (días de trabajo) más reseñas en GBP, Pit o Cuixa puede pasar de invisible a candidata #1 local en semanas-meses, porque sería la única pollería de la zona con web moderna + datos estructurados + llms.txt limpio.

---

## 6. Plan de acción priorizado

### P0 — Correcciones críticas (implementables en esta rama)
1. **Crear `public/img/og-image.jpg`** (1200×630, marca + "Pollería a Torredembarra") — arregla og:image y JSON-LD `image`.
2. **Home con contenido real:** renderizar `home.hero.title`/`home.hero.subtitle` como H1/h2, añadir bloque NAP visible (dirección Hort de l'Oca 12, teléfono, horario) y enlaces a menú/FAQ.
3. **Unificar NAP:** footer/FAQ → "Carrer Hort de l'Oca, 12, 43830" (la dirección real, corroborada por Uber Eats y reseñas); unificar coordenadas; revisar el teléfono histórico 977 64 18 05.
4. **Limpiar el catálogo del scraper** antes de que llegue a llms.txt/JSON-LD: filtrar gastos de envío, items corruptos ("Estómac", "BOND"), deduplicar (Fideuà ×2). Añadir whitelist/validación en el scraper o en el generador de llms.txt.
5. **Restaurant JSON-LD completo:** `priceRange`, `servesCuisine` (["Catalan","Spanish","Rotisserie"]), `acceptsReservations`, `hasMap`, `sameAs` (Uber Eats, Glovo, LinkedIn, Instagram/Facebook si existen), `menu`; vincular el `FoodEstablishment` del menú con `@id: …/#business` o eliminarlo.
6. **Titles/descriptions con keywords:** `menu.title` → "Carta · Menú — Pollería Pit o Cuixa Torredembarra" (+ variantes ca/en); `faq.title` → "Preguntes freqüents — Pit o Cuixa, polleria a Torredembarra"; H1 del menú localizado (i18n).
7. **`lastmod` en sitemap** (usar `filemtime` del sync o timestamp de última sincronización del scraper).

### P1 — Construir visibilidad
8. **301 www→non-www y http→https** en `.htaccess` y `web.config`.
9. **`X-Robots-Tag: noindex`** en `/api/*` públicas; emitir `<meta name="robots" content="noindex">` real en admin (usar el `'index' => false` existente).
10. **llms.txt multiidioma** (al menos ca+es) y con solo el catálogo limpio.
11. **Sección de reseñas/testimonios textuales** en home o página propia (con `aggregateRating` solo si se obtienen de fuente verificable y propia).
12. **Página "Quienes somos / Historia"** ("des de 1998", Pollastre Groc Català, Alimentbarna) — es el contenido de entidad y confianza que las IAs citan.
13. **Política explícita de crawlers de IA en robots.txt** (secciones GPTBot/ClaudeBot/PerplexityBot/Google-Extended con Allow, decisión consciente de entrenamiento).
14. **Verificación Search Console + Bing Webmaster** (metas o DNS) y solicitar reindexación de home y /menu — imprescindible para purgar el snippet de WordPress.
15. **Quitar CSS duplicado del layout** (CWV).

### P2 — Ventaja estructural
16. URLs citables por categoría de menú (`/menu#pollo`, o `/carta/pollo`) con `BreadcrumbList`.
17. Contenido de zonas de reparto (páginas o secciones por núcleo: Altafulla, Creixell, La Móra…) y unificación del nombre La Nou/Riera de Gaià.
18. Contenido estacional EN (takeaway playa, verano).
19. Refactor i18n a URLs por ruta (`/ca/menu`, `/es/menu`) en vez de `?lang=` + cookies.
20. Arreglar `manifest.json` `lang: "ca"` + descripción traducida.

### Off-site (no es código, pero es la mitad del juego)
21. **Google Business Profile**: verificar/crear, NAP exacto, categoría "Pollería/Rostisseria", fotos, productos, y **plan activo de reseñas** (pedir reseña tras cada pedido). Es el factor #1 del pack local y hoy los competidores ganan por reseñas en directorios.
22. **Directorios:** reclamar/actualizar fichas en sluurpy, ilmondodelpollo, TripAdvisor si existe; asegurar NAP idéntico.
23. **Consistencia de precios** web ↔ Uber Eats ↔ Glovo (o al menos explicar el diferencial de canal).

---

## 7. Riesgos y notas

- **Entidad confundible:** "Pit I Cuixa Gourmet SL / Rostisseria Pit I Cuixa SCIV" (Santa Coloma de Gramenet) comparte nombre casi exacto. `sameAs` + NAP + GBP correctos son la desambiguación.
- **El snippet obsoleto puede persistir semanas** tras corregir; Search Console + `lastmod` + contenido real en la home aceleran el re-rastreo.
- **El scraper es la fuente del catálogo:** cualquier limpieza debe ser idempotente (en el proceso de sincronización, no parcheo manual de la BD), o la basura volverá en la siguiente sincronización de cron-job.org.
- No se pudo verificar desde el repo el estado real del GBP ni las reseñas en Google Maps — pendiente de confirmar con el propietario.
