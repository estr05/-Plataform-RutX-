# RutX Web — Guidelines de Desarrollo

**Versión:** 1.6 · **Fecha:** 14 de agosto de 2026 · **Autor:** Manus AI  
**Vigencia:** obligatorio para todo commit, pull request y release del módulo web RutX (oficina/administración).  
**Ámbito:** Desarrollo y seguridad de RutX Web y su consumo servidor a servidor de `/api/v2/web/*`; excluye API móvil y el administrador local del Sincronizador.  
**Relación documental:** [`Mapa_Documental_RutX_Web.md`](Mapa_Documental_RutX_Web.md) define el propósito de cada documento; el plan visual define la apariencia; el contrato v2 define datos y endpoints; este archivo define **cómo se construye y protege** el código.

Este documento establece las reglas que rigen el desarrollo de la plataforma web RutX. Complementa el [`Plan_Estructura_Visual_Plataforma_Web_RutX.md`](Plan_Estructura_Visual_Plataforma_Web_RutX.md) (estructura y diseño visual), [`Contrato_API_Web_V2.md`](Contrato_API_Web_V2.md) (contrato exclusivo de oficina), [`Plan_Sprints_Semana_1.md`](Plan_Sprints_Semana_1.md) (secuencia de ejecución) y la guía técnica `DesarrolloSeguroconLaravel12.md` (v2.0). Donde haya conflicto, este `guidelines.md` manda por ser la regla de trabajo del equipo.

---

## 0. Principios rectores

> **El web es un consumidor de APIs.** La plataforma web no contiene datos, no define esquemas, no ejecuta migraciones y no toca bases de datos directamente. Todo —login incluido— pasa por la capa de APIs (Sincronizador + BD complementaria).

> **Lo simple gana.** Una vista Blade que renderiza componentes es mejor que una vista inteligente. Un componente genérico más es mejor que dos componentes casi iguales.

> **Nada se decide dos veces.** Colores, tamaños, tipografía y estructura ya están decididos en el plan visual. El trabajo del desarrollador es ensamblar, no inventar.

### 0.1 Jerarquía documental y regla de cambio

| Documento | Decide | Se consulta cuando... | No debe duplicar |
|---|---|---|---|
| `Mapa_Documental_RutX_Web.md` | Contexto, jerarquía, responsable y vínculo de documentos. | Se inicia una tarea o cambia el alcance. | Reglas técnicas completas o cronogramas. |
| `Plan_Estructura_Visual_Plataforma_Web_RutX.md` | Topbar, sidebar, paleta, cards, etiquetas y arquetipos. | Se crea o altera una pantalla/componente. | Endpoints o políticas de seguridad. |
| `Contrato_API_Web_V2.md` | URL, DTO, JSON, permisos, errores y auditoría de `/api/v2/web/*`. | Se consume o crea un endpoint. | HTML, CSS o rutas móviles. |
| `guidelines.md` | Seguridad, estructura de código, calidad y PR. | Se implementa, revisa o despliega código. | Detalle visual o catálogo de datos. |
| `Plan_Sprints_Semana_1.md` | Orden diario, definición de hecho y entregables. | Se decide qué construir hoy. | Normas permanentes del proyecto. |

Todo cambio que afecte más de un documento se registra primero en el mapa documental y después se actualizan las fuentes de verdad afectadas en el mismo pull request.

---

## 1. Seguridad

### 1.1 Regla de oro API-first

- Prohibido configurar conexiones de base de datos propias en el web (ni `database.php` con credenciales de Firebird/MySQL, ni Eloquent, ni migraciones para datos de negocio).
- Toda escritura con impacto en Microsip (cancelaciones, traspasos, avisos) se realiza **exclusivamente** a través de los endpoints del Sincronizador. El web nunca envía consultas a Microsip.
- El `ApiClient` (`App\Services\ApiClient`) es el único punto de salida HTTP hacia las APIs. Ninguna vista ni controlador puede usar `Http::get(...)` directamente; siempre vía el cliente centralizado.

### 1.2 Autenticación y sesiones

- El login del portal se realiza **exclusivamente** con `POST /api/v2/web/auth/login`. Es un contrato nuevo e independiente de la app: emite JWT con `scope=web`, roles de oficina, permisos y zonas autorizadas. El login móvil existente `POST /api/auth/login` se conserva para vendedores y queda prohibido consumirlo desde RutX Web. El espacio `/api/v2/admin/*` ya existe (`AdminController`) pero es el panel de configuración local del Sincronizador: corre solo en localhost, **sin autenticación**, y nunca se expone a la red ni se consume desde el portal. El token web se guarda en la **sesión cifrada de Laravel** (`session()->put('api_token', ...)`), nunca en `localStorage`, cookies no seguras ni atributos `data-` visibles en el HTML.
- Sesiones con `Secure`, `HttpOnly` y `SameSite=Lax`; rotación de ID de sesión post-login (`session()->regenerate(true)`).
- El token se envía como header `Authorization: Bearer` desde el servidor (en las llamadas server-side de Livewire), no desde el navegador del cliente.
- Rate limiting estricto: máximo 5 intentos de login por minuto por IP (`throttle:5,1`) y bloqueo progresivo.
- Logout: en el primer release se invalida la sesión local de Laravel (`session()->flush()`). Cuando el contrato v2 active refresh tokens, se consumirá `POST /api/v2/web/auth/logout` para revocación remota. Un token móvil jamás sirve para cerrar ni renovar una sesión web.

### 1.3 Autorización

- Los permisos se evalúan **doble**: (a) el rol del usuario se valida localmente (middleware `role:` sobre las rutas web) y (b) la API remota valida el mismo permiso; no se confía en que la API "ya lo hizo".
- Las acciones sensibles (cancelar venta, traspasar cliente, emitir notificación) requieren `@can` en Blade **y** `$this->authorize(...)` en el componente Livewire. No basta con ocultar el botón.
- **Coyatoc — roles activos del MVP:** `administrador` (operación autorizada), `supervisor` (solo sus rutas/zonas) y `lector` (consultas de lectura autorizadas). La matriz concreta se valida con Coyatoc en la política de cancelaciones.
- **Rol reservado, no activo:** `contador` queda registrado desde la primera versión para evitar rediseñar autenticación/permisos después, pero no se emite en tokens, no se asigna a usuarios Coyatoc y no muestra navegación adicional. Cuando un cliente contrate el alcance contable, recibe únicamente `dashboard.financial.read`, `reports.financial.read`, `sales.financial.read`, `collections.read` y, si se aprueba, `exports.financial.request`. Se le deniegan expresamente agenda, mapa/geolocalización, clientes de contacto/ubicación, inventario operativo, cancelaciones, notificaciones y configuración. La API impone estas restricciones aunque la interfaz no muestre esas vistas.

### 1.4 Entrada de datos y formularios

- Toda entrada se valida con **Form Requests** (`App\Http\Requests`) con reglas de tipo, rango y formato; prohibido `$request->all()` sin filtro y prohibido confiar en validación solo del lado cliente.
- Los payloads hacia la API se construyen a partir de los datos validados (`$request->validated()`), no de los crudos.
- Montos monetarios y cantidades se validan como `numeric` con límites explícitos; nunca se pasan strings a la API.

### 1.5 JSON seguro: Laravel → `/api/v2/web/*`

La interfaz del usuario nunca consume el Sincronizador desde JavaScript. El navegador se comunica con Laravel mediante rutas protegidas y Livewire; **Laravel es el único intermediario** que intercambia JSON con el Sincronizador a través de `App\Services\ApiClient`. Esta regla mantiene el JWT fuera del navegador y evita habilitar CORS innecesario.

| Regla | Implementación obligatoria | Prohibido |
|---|---|---|
| Formato | Todas las llamadas establecen `Accept: application/json` y `Content-Type: application/json`; `ApiClient` usa `Http::acceptJson()->asJson()`. | Enviar formularios, query strings o JSON armado manualmente para comandos. |
| Datos salientes | Los cuerpos se crean desde `$request->validated()` o DTO/array tipado; solo se mandan campos previstos por el contrato. | Reenviar `$request->all()`, datos Livewire completos o propiedades ocultas. |
| Autenticación | `withToken(session('api_token'))` ocurre solo en el servidor; el token nunca entra en HTML, cookies legibles o `localStorage`. | Llamadas `fetch`/Axios directas al Sincronizador desde el navegador. |
| Transporte | `API_WEB_BASE_URL` debe ser HTTPS y verificar certificado TLS; conexión y respuesta tienen timeout explícito. | `withoutVerifying()` en producción, HTTP plano o timeout infinito. |
| Respuesta | El cliente acepta únicamente el envelope del contrato (`data`, `meta`, `trace_id` o `code`, `message`, `errors`, `trace_id`); valida estado y JSON antes de mapearlo. | Asumir que una respuesta 200 es válida o mostrar contenido remoto sin validar. |
| Errores | Se conserva `trace_id` en log seguro; el usuario ve un mensaje funcional sin token, payload ni excepción. | Mostrar stack trace, cuerpo de error completo o encabezados de la API. |
| Reintentos | Solo `GET` y comandos con `idempotency_key` pueden reintentarse de forma acotada. | Reintentar automáticamente cancelaciones, avisos o asignaciones sin idempotencia. |
| Cambios críticos | Agenda en lote, cancelaciones, traspasos y avisos incluyen `Idempotency-Key` y se auditan. | Duplicar acciones al doble clic o confiar solo en deshabilitar el botón. |

El Sincronizador responde JSON en `snake_case` según su configuración actual. Laravel conserva esa forma en `ApiClient`/DTOs y solo traduce al texto español en componentes Blade; no se crean adaptadores diferentes por pantalla.

### 1.6 Transporte y headers

- HTTPS obligatorio en todos los ambientes (producción y pruebas).
- Middleware global de headers de seguridad: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` sin cámara/micrófono/geolocalización para la UI.
- CSRF habilitado para las rutas web de Laravel (formularios con `@csrf`); las llamadas Livewire lo llevan automático.

### 1.7 Credenciales y secretos

- `.env` jamás versionado. `.env.example` solo expone la configuración del contrato web: `API_WEB_BASE_URL`, `API_WEB_TIMEOUT`, `API_WEB_CONNECT_TIMEOUT`, `API_WEB_AUTH_URL`, `API_WEB_CLIENT_ID`, `API_WEB_CLIENT_SECRET` y `API_WEB_VERIFY_TLS=true` (nunca tokens de usuario). Se prohíbe configurar raíces `/api/v1/*` móvil o `localhost:5047/api/v2/admin/*` local en RutX Web.
- Los secretos de la API se leen de variables de entorno; prohibido hardcodear llaves, IPs o endpoints internos en el código.
- Los errores de la API remota se loggean en el servidor (canal `api_errors`), nunca se exponen trazas completas al navegador; al usuario solo llega el mensaje de usuario definido por el endpoint.

### 1.8 Registro de operaciones

- Toda acción destructiva o sensible (cancelación, traspaso, cambio de usuario/rol, exportación masiva) registra **auditoría en la API** (quién, cuándo, IP de origen, motivo cuando aplique). El web incluye el motivo y la trazabilidad en cada payload; no inventa registros locales.

---

## 2. Reglas de desarrollo

### 2.1 Separación de responsabilidades (estricto)

| Capa | Qué contiene | Qué NO contiene |
|------|--------------|-----------------|
| Vistas Blade | Solo HTML + componentes `<x->` y directivas `@props/@can` | Lógica, arrays de datos, cálculos, CSS arbitrario |
| Componentes Livewire | Estado, llamadas al `ApiClient`, eventos | HTML extenso inline (delegar a componentes Blade) |
| `App\Services\ApiClient` | HTTP solo hacia `/api/v2/web`, token web, timeouts y mapeo de errores | Lógica de negocio, rutas móviles `/api/v1/*` o administración local `/api/v2/admin/*` |
| `App\Services\*` | Transformación de respuestas de API a datos de pantalla | HTML, dependencias de vista |

- Vistas Volt inline quedan reservadas a formularios triviales de menos de ~30 líneas. Todo módulo de negocio usa Livewire con clase PHP (`make:livewire`).
- Prohibido mock data embebido en vistas o componentes (arrays de ventas ficticias, clientes falsos). Si un endpoint no está listo, se usa un servicio stub con la misma interfaz y se marca con un comentario `@stub`.

### 2.2 Nombres y organización

- Rutas agrupadas por módulo en `routes/modules/*.php`, todas bajo un prefijo común (`/` con middleware `auth.session`); sin rutas sueltas en `web.php`.
- Controladores de página: uno por módulo, métodos simples que retornan `view()`. Componentes Livewire: un directorio por módulo bajo `app/Livewire/`.
- Nombres en inglés para código (`Sale`, `RouteMonitor`, `StatusBadge`), español para etiquetas de usuario. Fechas y monedas siempre formateadas por componentes (`<x-currency>`, `Carbon::locale('es')`), nunca hardcodeadas en vistas.

### 2.3 Rendimiento

- Cada vista de listado usa **paginación de API** (`?page=&per_page=25`); prohibido traer listas completas a memoria.
- Los componentes Livewire de monitoreo (jornada, mapa) definen su intervalo de polling explícito (10–30 s) y lo documentan en un comentario sobre la propiedad `$pollInterval`.
- Chart.js y Leaflet se importan vía npm/Vite; prohibido cualquier `<script src="cdn...">` en las vistas.

### 2.4 Complejidad

- Un componente Blade no excede ~250 líneas; si crece, se descompone en sub-componentes.
- Un método de componente Livewire no excede ~40 líneas.
- Prohibidas las vistas "todoterreno" que cambian de estructura según un flag (ej. la misma vista para modo tabla y modo gráfico): son dos componentes.

---

## 3. Estandarización

### 3.1 Estructura idéntica por módulo

Todo módulo nuevo replica la anatomía del plan visual (breadcrumb → título → `<x-filter-bar>` → KPIs → contenido → totales). La plantilla de inicio de módulo (`resources/views/modules/_scaffold.md`) se usa para arrancar cada uno y se adjunta al PR.

### 3.2 Componentes base obligatorios

Se usa **siempre** la lista del plan visual (`<x-data-table>`, `<x-kpi-card>`, `<x-filter-bar>`, `<x-date-range>`, `<x-chart>`, `<x-status-badge>`, `<x-currency>`, `<x-modal>`, `<x-alert>`, `<x-loading-state>`, `<x-map-view>`, `<x-notification-bell>`, `<x-page-header>`). Antes de escribir un elemento nuevo: **(1)** ¿existe un componente que lo cubra? → úsalo. **(2)** ¿le falta una variante? → extiéndelo con props, no lo clones. **(3)** Solo si no aplica nada, se propone un componente nuevo con justificación en el PR.

### 3.2.1 Iconografía obligatoria: sin emojis

- Queda prohibido usar **emojis o caracteres emoji Unicode** como recursos de interfaz. La prohibición comprende navegación, botones, acciones, KPIs, alertas, estados, tablas, filtros, tooltips, vacíos, carga, errores y texto que forme parte de la UI. El contenido introducido por el usuario se preserva sin modificar, pero la aplicación no genera ni propone emojis propios.
- Todo significado visual se comunica con un **ícono SVG semántico** de la biblioteca aprobada por el proyecto, conforme al catálogo y los nombres definidos en el plan visual. Un mismo concepto conserva el mismo ícono en todos los módulos; no se sustituyen acciones equivalentes por íconos distintos.
- Los íconos deben acompañarse de una etiqueta visible cuando haya espacio. Cuando una acción se represente solo con ícono, el control requiere `aria-label` en español y tooltip; los íconos decorativos usan `aria-hidden="true"`. Ningún ícono es el único medio para comunicar estado, error, permiso o resultado.
- Como referencia base, los íconos de navegación, tarjetas y acciones usan **20px** y heredan el color semántico del componente. Los controles de solo ícono conservan un objetivo táctil mínimo de **40×40px**. Cualquier tamaño, variante o biblioteca adicional exige justificación en el PR y aprobación de diseño.

### 3.3 Convenciones

| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| Componentes Blade | kebab-case | `status-badge`, `data-table` |
| Clases PHP | PascalCase singular | `StatusBadge`, `SaleReport` |
| Tokens CSS | `--rutx-*` | `--rutx-primary`, `--rutx-accent` |
| Clases Tailwind en HTML | utilidades semánticas | `bg-rutx-primary`, `text-rutx-accent` |
| Variables JS/PHP | camelCase | `$pollInterval`, `apiToken` |
| Constantes | UPPER_SNAKE_CASE | `MAX_POLL_INTERVAL` |
| Idioma de UI | Español de México | "Cancelación", "Última venta" |

### 3.4 Formateo automático

- `composer require laravel/pint --dev`; `php artisan pint` antes de todo commit. CI rechaza PRs sin Pint limpio.
- `npm run lint` sobre CSS/JS con reglas básicas (sin hex, sin `!important` salvo justificación documentada).

---

## 4. Mantenibilidad

- **Un archivo decide los estilos:** `resources/css/tokens.css` (tokens) + `app.css` (base). Cualquier color, radio o fuente nueva se declara ahí; si no está en los tokens, no existe.
- **Auditoría visual en CI:** un job de CI ejecuta `grep -rE '#[0-9a-fA-F]{6}' resources/views resources/css` y falla si encuentra hex literales fuera de `tokens.css`.
- **Documentación viva:** cada componente Blade documenta sus props al inicio del archivo; cada módulo tiene un `README.md` corto con endpoints de API que consume. Del lado del Sincro, todo endpoint del portal se registra en `Docs/CONTRATOS_WEB_V2.md`; `Docs/CONTRATOS.md` permanece reservado para la app móvil.
- **Regla del PR:** mínimo un revisor; checklist del §8 completado; ningún PR mergea con pipelines en rojo.
- **Deprecación, no borrado:** un componente no se elimina mientras exista uso; se marca `@deprecated` y se elimina en el siguiente release mayor.
- **Tests focalizados:** como no hay BD local, los tests se concentran en: validación de Form Requests, construcción de payloads del `ApiClient` (con mocking HTTP de Laravel), y renderizado de componentes clave (`assertSee` de arquetipos). Cobertura mínima sobre Services del 70 %; las vistas no requieren cobertura de comportamiento.
- **Criterio de aceptación de arquetipos con JS:** todo componente Blade que incluya inicialización JavaScript propia (Chart.js, Leaflet, Alpine) **debe superar un test de re-render con `wire:poll` antes del merge a la rama de integración**. El test verifica que la inicialización es idempotente o re-disparable (el componente no lanza excepciones JS ni queda congelado tras el segundo render). En producción esto se valida vía el evento `rutx:refresh-*` capturado por el módulo JS; en el playground se acepta un `setInterval` Alpine equivalente. Precedente: H-01 (`<x-chart>`) y H-02 (`<x-map-view>`) detectaron el defecto en Sprint 3 por ausencia de esta regla.

---

## 5. Diseño y consistencia visual

### 5.1 Fuentes únicas de verdad

1. `resources/css/tokens.css` — colores, radios, alturas, fuentes.
2. `Plan_Estructura_Visual_Plataforma_Web_RutX.md` §3-4 — especificación de objetos y componentes.
3. `DesarrolloSeguroconLaravel12.md` §7 — paleta semántica de origen (`app_theme.dart`).

### 5.2 Reglas visuales (inquebrantables)

- **Cero hexadecimales** fuera de `tokens.css`. Se audita en CI y en revisión de PR.
- **Una tipografía de UI (Inter)** y **una de datos técnicos (JetBrains Mono)**. Prohibido importar fuentes adicionales o usar `font-[...]` arbitrarios en vistas.
- **Los montos en JetBrains Mono, alineados a la derecha.** Los totales destacados usan `--rutx-accent`; el resto usa `--rutx-text`.
- **Estados siempre con color + etiqueta** (`<x-status-badge>`): nunca una celda que comunique estado solo por color.
- **Botones: 48px de alto, radio 12px**, paleta de tipos de §3.4 del plan visual. Prohibido botones de tamaños inventados.
- **Tarjetas: blanco, borde 1px `--rutx-border`, radio 8px**, padding 16px. Prohibido tarjetas sin borde o con sombras fuertes.
- **Inputs: fondo gris claro, radio 8px, focus con borde primario de 2px.**
- **Accesibilidad AA:** contraste mínimo 4.5:1 (3:1 en texto grande), foco visible en todo elemento interactivo, botones 48px como objetivo táctil mínimo.
- **Gráficas: paleta centralizada exclusivamente** (`chartBlue`, `infoCyan`, `secondary` y derivados). Prohibido series con colores propios.

### 5.3 Excepciones

Cualquier desviación de las reglas 5.2 requiere: justificación escrita en el PR, aprobación del revisor y, si introduce un valor nuevo (color, radio), su declaración en `tokens.css` con nombre semántico. Las excepciones "porque se ve mejor" no son válidas.

---

## 6. Checklist obligatorio de Pull Request

| # | Verificación | Área |
|---|--------------|------|
| 1 | Pipeline verde: Pint, lint de estilos, tests | Calidad |
| 2 | Sin hex literales en vistas/CSS (auditoría CI) | Visual |
| 3 | Todo componente nuevo usa los arquetipos o los extiende | Visual |
| 4 | Sin lógica ni datos en vistas; sin mock data | Desarrollo |
| 5 | Toda entrada validada con Form Request; payloads desde `validated()` | Seguridad |
| 6 | Acciones sensibles con `@can` + `$this->authorize` | Seguridad |
| 7 | Acciones destructivas llevan motivo/auditoría en el payload | Seguridad |
| 8 | Listados con paginación de API; sin traer listas completas | Desarrollo |
| 9 | Sin CDNs: Chart.js/Leaflet/fuentes vía npm o `public/fonts` | Mantenibilidad |
| 10 | Contraste AA y foco visible verificados visualmente | Visual |
| 11 | README del módulo actualizado con los endpoints que consume | Mantenibilidad |
| 12 | Un revisor aprobó; componentes nuevos documentan sus props | Proceso |

---

## 7. Glosario rápido

| Término | Significado en este proyecto |
|---------|------------------------------|
| Arquetipo | Componente base reutilizable de la plataforma (`<x-data-table>`, etc.) |
| BD complementaria | Base del Sincronizador que sirve los endpoints; el web **nunca** la consulta directo |
| Form metálico | Estética definida en el plan visual: superficies grises frías, azul profundo, acento naranja medido |
| Microsip | ERP Firebird de la empresa; fuente maestra de datos, intocable desde el web |
| Sincronizador | Servicio que escribe en Microsip y expone las APIs que consume el web; para el portal, el único contrato permitido es `/api/v2/web/*` |
| Stub | Implementación falsa con interfaz real, marcada `@stub`, solo mientras el endpoint no existe |

---

*Este documento entra en vigor con el primer PR del repositorio de producción. Las modificaciones se proponen en PRs al propio `guidelines.md` y requieren aprobación de todo el equipo.*
