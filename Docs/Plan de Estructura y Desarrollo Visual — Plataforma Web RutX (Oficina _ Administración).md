# Plan de Estructura y Desarrollo Visual — Plataforma Web RutX (Oficina / Administración)

**Autor:** Manus AI · **Fecha:** 14 de agosto de 2026 · **Versión:** 1.4  
**Estado:** Especificación visual y de navegación aprobada para construcción.  
**Ámbito:** Portal de oficina RutX: navegación, componentes y apariencia; consume únicamente el contrato `/api/v2/web/*` y excluye móvil y administrador local.  
**Documentos relacionados:** [`Mapa_Documental_RutX_Web.md`](Mapa_Documental_RutX_Web.md) (índice y jerarquía), [`guidelines.md`](guidelines.md) (reglas obligatorias), [`Contrato_API_Web_V2.md`](Contrato_API_Web_V2.md) (datos y endpoints), [`Plan_Sprints_Semana_1.md`](Plan_Sprints_Semana_1.md) (orden de construcción), `DesarrolloSeguroconLaravel12.md` (guía Laravel y sistema visual de origen), `Plan_Ejecucion_Web_RutX.md` (roadmap de producto) e inventario funcional VeMobile (Anexo A).

---

## 1. Premisas y alcance

Este documento define la **estructura de la plataforma web** y sus **elementos visuales principales**, siguiendo tres premisas fijadas por el equipo:

1. **API-first, sin migraciones de Laravel.** Toda la comunicación con los datos —incluyendo login, sesión y autorización— se realiza mediante **`/api/v2/web/*`** del Sincronizador; Laravel es una capa de presentación segura que consume ese contrato. No existe `$fillable`, Eloquent contra datos de negocio ni `php artisan migrate` para el portal. La configuración técnica que ya existe en `localhost:5047/api/v2/admin/*` se usa únicamente al instalar/integrar el Sincronizador en la PC del cliente: es independiente, no se expone a internet y no se integra al diseño, navegación ni `ApiClient` de RutX Web.
2. **Solo componentes nativos de Laravel.** Blade, Blade components (`<x->`), Livewire, Vite y Tailwind CSS 4. Sin React, sin Vue, sin librerías de UI externas (ni Material, ni Bootstrap, ni Alpine si se puede evitar).
3. **Estética metálica, sencilla y funcional.** La interfaz sigue la identidad RutX de la app móvil (`app_theme.dart`), interpretada como una superficie "metálica": grises fríos, azules profundos, acentos naranjas medidos, bordes finos y sombras discretas. Nada de gradientes llamativos ni dark mode forzado.

El alcance visual se centra en lo que **realmente necesita el cliente** (los 5 requerimientos de la llamada y el inventario VeMobile): dashboard con KPIs, reportes con tablas, monitoreo de rutas con mapa, cancelaciones y notificaciones. Cada elemento visual que se define aquí existe porque resuelve uno de esos casos.

---

## 2. Estructura de la plataforma

### 2.1 Arquitectura por capas (presentación)

Como el web no toca bases de datos, la única capa propia es la de **presentación**, organizada así:

```text
app/
├── Livewire/                    # Estado de pantalla y coordinación con ApiClient
├── Services/
│   └── ApiClient.php            # Único cliente HTTP: solo /api/v2/web/*
└── View/Components/             # Clases de arquetipos Blade

config/
└── navigation.php               # Fuente única: módulo → vistas → permiso

resources/
├── css/
│   ├── tokens.css               # ÚNICA fuente de colores, tipografía, radios y espaciado
│   └── app.css                  # Importa tokens.css, Tailwind 4 y estilos base
├── js/
│   └── app.js                   # Chart.js y Leaflet desde npm/Vite; sin CDN
└── views/
    ├── layouts/
    │   └── app.blade.php        # Chasis: topbar + sidebar izquierdo + contenido
    ├── components/              # Arquetipos reutilizables de la sección 4
    └── modules/                 # Una carpeta por módulo de negocio
        ├── clientes/
        ├── producto/
        ├── inventario/
        ├── venta/
        ├── ruta/
        └── configuracion/

routes/
└── modules/                     # Rutas web agrupadas por módulo; nunca llamadas API directas
```

La regla estructural es **un directorio por módulo de negocio**, reflejando los módulos que el cliente usa en VeMobile. Dentro de cada módulo, Livewire maneja la pantalla completa (estado + datos) y las vistas solo renderizan componentes.

### 2.2 Layout general (el "chasis" de todas las pantallas)

La navegación se organiza en **dos niveles explícitos y fijos**, tal como lo define el mock aprobado de la plataforma: el **topbar** (barra superior) alberga los módulos del sistema y el **sidebar izquierdo** alberga únicamente las vistas del módulo activo. El área de trabajo queda al centro-derecha.

```text
┌─────────────────────────────────────────────────────────────────────┐
│ TOPBAR (64px, fondo rutx-primary)                                   │
│  RUTX · (Sincronización ● CONECTADO)    [Cliente][Producto]         │
│                                     [Inventario][Venta][Ruta][Conf] │
├──────────────┬──────────────────────────────────────────────────────┤
│ SIDEBAR      │  ÁREA DE CONTENIDO (fondo rutx-bg)                   │
│ (256px,      │  Breadcrumb → H1 → filtros → KPIs → tablas/gráficas  │
│  rutx-dark)  │                                                      │
│              │                                                      │
│ VENTA        │                                                      │
│  Levantamiento                                                    │
│  Pedidos ...                                                      │
│              │                                                      │
└──────────────┴──────────────────────────────────────────────────────┘
```

| Zona | Contenido | Regla visual |
|------|-----------|--------------|
| **Topbar** (altura 64px) | Logo **RUTX**, pill de estado de sincronización (`Sincronización ● CONECTADO` / `SIN CONEXIÓN`), y a la derecha las **pestañas de módulo**: Cliente, Producto, Inventario, Venta, Ruta, Configuración | Fondo `rutx-primary`; logo y pill en blanco; pestaña activa con subrayado/ícono `rutx-accent` |
| **Sidebar izquierdo** (256px) | **Solo las vistas del módulo seleccionado en el topbar** (sección 2.3) | Fondo `rutx-surface-dark`; título del módulo en la cabecera; vistas listadas con ícono; la vista activa resaltada |
| **Área de contenido** | Breadcrumb, título H1, barra de filtros contextual, tarjetas/tablas/mapas | Fondo general `rutx-bg`; tarjetas sobre `rutx-surface` |

> Regla dura: **una sola plantilla `layouts/app.blade.php`** para todas las pantallas autenticadas. Si una pantalla necesita un layout distinto, debe justificarse en el PR; no se crean layouts "por si acaso".

La pill de sincronización es funcional y no decorativa: refleja el estado de conexión de la plataforma con la API del Sincronizador (verde `rutx-status-success` = conectado, gris `rutx-status-unknown` = sin conexión) y se refresca con el mismo intervalo de polling del monitoreo.

### 2.3 Navegación: topbar de módulos + sidebar contextual izquierdo

La navegación tiene dos niveles con responsabilidades separadas, siguiendo el mock aprobado:

1. **Topbar = módulos** (navegación horizontal). Los módulos del sistema — Cliente, Producto, Inventario, Venta, Ruta y Configuración — se muestran como pestañas de ícono + etiqueta en la barra superior. Al hacer clic en una pestaña, el sidebar se reconstruye con las vistas de ese módulo y el área de contenido carga su vista por defecto.
2. **Sidebar izquierdo = vistas del módulo activo** (navegación vertical contextual). El sidebar muestra **exclusivamente** las vistas que pertenecen al módulo seleccionado en el topbar; ninguna vista ajena es visible en ningún momento. Cambiar de pestaña en el topbar cambia por completo el contenido del sidebar.

#### 2.3.1 Mapa oficial módulo → vistas

| Módulo (pestaña del topbar) | Ícono sugerido | Vistas en el sidebar izquierdo |
|-----------------------------|----------------|--------------------------------|
| **Cliente** | `user-group` | Clientes, Traspaso de cliente |
| **Producto** | `cube` | Productos, Listas de precio, Precios por zona |
| **Inventario** | `archive-box` | Inventario por ruta, Productos rechazados, Mermas |
| **Venta** | `shopping-cart` | Levantamiento, Pedidos, Cobranza, Utilidad, Depósito Venta, Nuevo Gasto Operativo, Reporte de Ventas por Cliente, Reporte Rentabilidad por Ruta, Clientes con Mayor Venta, Productos Rechazados, Reporte Preventa Entrega, Clientes Pendientes, Visor, Reportes y Gráficas, Reportes Globales |
| **Ruta** | `truck` | Mapa en tiempo real, Jornada del día, Agenda, Kilometraje |
| **Configuración** | `cog-6-tooth` | Usuarios, Roles, Zonas |

> Nota: el mock concentra las vistas de reportes dentro del módulo **Venta** (Reportes y Gráficas, Reportes Globales, Rentabilidad, Ventas por Cliente…), replicando el submenú de Venta que el cliente ya conoce de VeMobile. Los reportes de negocio quedan así en su contexto natural; no existe un módulo "Reportes" separado en el topbar.

La navegación se deriva de permisos declarados en `config/navigation.php`: un usuario con rol `lector` ve solo las pestañas y vistas de lectura autorizadas; las vistas sin permiso **no se renderizan** (nunca se muestran en gris o deshabilitadas — la ocultación es conveniencia, la autorización se valida en cada ruta).

> **Extensión reservada:** `contador` está contemplado como rol futuro de consulta financiera. Para Coyatoc permanece deshabilitado y la navegación no cambia. Cuando se active para otro cliente, `config/navigation.php` mostrará únicamente vistas de lectura permitidas por `dashboard.financial.read` o `reports.financial.read`; no habilitará Agenda, Mapa, Clientes con datos de contacto/ubicación, Inventario, acciones de Venta, Notificaciones ni Configuración.

#### 2.3.2 Estado visual del topbar

| Estado | Representación |
|--------|----------------|
| Pestaña de módulo inactiva | Ícono + etiqueta en blanco al 70 %; al hover, blanco al 100 % con fondo blanco al 8 % |
| **Pestaña de módulo activa** | Ícono + etiqueta en blanco al 100 %, **subrayado inferior de 3px en `rutx-accent`** y bloque de fondo `rutx-primary-dark` |
| Badge de sincronización | Pill con punto de estado: `● CONECTADO` en `rutx-status-success` / `● SIN CONEXIÓN` en `rutx-status-unknown`; clic abre detalle de última sincronización |

#### 2.3.3 Estado visual del sidebar

El sidebar del módulo activo muestra en su cabecera el **título del módulo en mayúsculas** (ej. `VENTA`), seguido de la lista de sus vistas, cada una con ícono y etiqueta:

| Estado | Representación |
|--------|----------------|
| Vista inactiva | Texto blanco al 75 %, ícono blanco al 70 %; al hover, fondo blanco al 8 % |
| **Vista activa** | **Fondo `rutx-primary-dark` con borde izquierdo de 4px en `rutx-accent`** (como en el mock), texto e ícono blanco al 100 % |
| Vista sin permiso | No se renderiza |

El sidebar admite colapsarse a 64px (solo íconos de vista + título del módulo abreviado); al hover se despliega tooltip con el nombre de la vista. El breadcrumb del área de contenido replica la misma jerarquía (`Módulo · Vista`), de modo que topbar, sidebar y contenido hablan el mismo idioma.

#### 2.3.4 Regla dura de navegación (mantenibilidad)

Toda la navegación se declara **una sola vez**: el topbar como `<x-app-topbar>` y el sidebar como `<x-app-sidebar>`, ambos leyendo un único archivo de configuración `config/navigation.php` con la estructura `módulo → pestaña → [vistas]`. Para agregar un módulo, una pestaña o una vista se edita ese archivo y nada más — ni el topbar ni el sidebar se tocan. Así la navegación entre módulos y vistas mantiene la misma estructura y estilo en toda la plataforma.

### 2.4 Anatomía estándar de una pantalla

Toda pantalla del sistema sigue la misma composición de arriba hacia abajo. Esto garantiza que cualquier módulo nuevo "se vea igual" sin esfuerzo:

```
┌──────────────────────────────────────────────────────────┐
│  Breadcrumb · Título de pantalla (H1) · Fecha de corte  │
├──────────────────────────────────────────────────────────┤
│  Barra de filtros estándar (rango de fechas, zona, ruta, │
│  cadena…) · botones Consultar / Limpiar / Exportar       │
├──────────────────────────────────────────────────────────┤
│  [Fila de KPI-cards]  (solo si aplica)                   │
├──────────────────────────────────────────────────────────┤
│  [Gráfica] y/o [Tabla de datos] y/o [Mapa]              │
├──────────────────────────────────────────────────────────┤
│  Resumen / totales · estado de última sincronización     │
└──────────────────────────────────────────────────────────┘
```

Las únicas pantallas que rompen este flujo son el **mapa de monitoreo** (el mapa ocupa el papel principal y las KPI-cards pasan a un panel lateral colapsable) y el **detalle de una venta** (dos columnas: datos de la venta + acciones con cancelación).

### 2.5 Pantallas prioritarias (MVP, apegado al cliente)

Prioridad Alta: dashboard con KPIs del mes, mapa de rutas en tiempo real con jornada, ventas con cancelación, y los reportes base (productos, preventa-entrega, consolidado, por ruta) dentro del módulo Venta. La primera vista por defecto del sistema al iniciar sesión es **Venta · Reportes y Gráficas** (el panel que el mock presenta). Prioridad Media: clientes (catálogo y traspaso), notificaciones, configuración de usuarios/roles/zonas. Los módulos de VeMobile que no están en el MVP (unidades de reparto, agendas de entrega, ticket, catálogos de causas) quedan registrados como backlog y se incorporan registrándolos en `config/navigation.php` y construyéndolos con los mismos arquetipos; así crecen sin tocar el resto de la plataforma.

---

## 3. Identidad visual: formato metálico

### 3.1 Concepto

"Metálico" se traduce en la interfaz como **superficies planas de gris frío con biseles sutiles**: fondos gris-azulados muy claros, tarjetas blancas con borde fino, encabezados en el azul corporativo profundo, y un único punto de calor (naranja) para las acciones primarias y los totales importantes. La sensación debe ser la de una herramienta industrial limpia: precisa, sin ruido, sin adornos.

### 3.2 Paleta de colores (única fuente: `tokens.css`)

Se toma la paleta semántica de `app_theme.dart` (documentada en `DesarrolloSeguroconLaravel12.md`) y se organiza en roles para el web. **Un solo archivo de tokens** (`resources/css/tokens.css`) declarado en `app.css`; prohibidos los hexadecimales literales en el resto del proyecto.

| Rol | Token | Valor | Uso |
|-----|-------|-------|-----|
| Primario | `--rutx-primary` | `#003B5C` | Header, sidebar, textos de encabezado de tabla, enlaces activos |
| Primario oscuro | `--rutx-primary-dark` | `#002D47` | Hover de elementos primarios, footer |
| Acento | `--rutx-accent` | `#FF6A13` | Botones primarios, totales destacados, ítem de menú activo |
| Acento claro | `--rutx-accent-light` | `#FFF3E0` | Hover secundarios, fondos de énfasis suave |
| Secundario | `--rutx-secondary` | `#A8C8E9` | Fondos secundarios, marcas de rango en gráficas |
| Fondo | `--rutx-bg` | `#F4F6F8` | Fondo general de pantallas (superficie metálica) |
| Superficie | `--rutx-surface` | `#FFFFFF` | Tarjetas, paneles, inputs |
| Superficie gris | `--rutx-surface-grey` | `#F5F5F5` | Filas alternas de tablas, campos deshabilitados |
| Texto primario | `--rutx-text` | `#343D45` | Texto de lectura |
| Texto secundario | `--rutx-text-muted` | `#6B7680` | Etiquetas, captions, hints |
| Borde | `--rutx-border` | `#E0E0E0` | Bordes de tarjetas, inputs, separadores de tabla |
| Éxito | `--rutx-status-success` | `#2E7D32` | Estado enviada / ok |
| Advertencia | `--rutx-status-warning` | `#E65100` | Pendiente / en proceso |
| Error | `--rutx-status-error` | `#C62828` | Cancelada / rechazada / error |
| Desconocido | `--rutx-status-unknown` | `#9E9E9E` | Sin estado / sin conexión |

Las gráficas usan una subpaleta propia también centralizada (`chartBlue` `#005691`, `infoCyan` `#0277BD`, `secondary` `#A8C8E9` y tres derivados claros), de modo que ningún reporte pueda salir con colores inventados.

### 3.3 Tipografía

Una sola familia para interfaz y una para datos técnicos, cargadas de forma local (`public/fonts/`, `@font-face` woff2) para no depender de CDN:

| Nivel | Familia | Peso | Tamaño | Interlineado |
|-------|---------|------|--------|--------------|
| H1 título de página | Inter | 700 | 30px | 1.2 |
| H2 sección | Inter | 700 | 24px | 1.25 |
| H3 sub-sección | Inter | 600 | 20px | 1.3 |
| Cuerpo y celdas de tabla | Inter | 400 | 16px | 1.5 |
| Etiquetas y captions | Inter | 500 | 12px | 1.4 |
| Badges de estado | Inter | 600 | 12px | 1.4 |
| Moneda, claves, datos técnicos | JetBrains Mono | 400 | 14px | 1.5 |

Los montos monetarios (MXN) se renderizan **siempre en JetBrains Mono** alineados a la derecha: es la regla que hace que las tablas financieras se lean de un vistazo.

### 3.4 Formatos de los objetos y componentes principales

#### Cards (tarjetas)

Fondo blanco, borde `1px` en `--rutx-border`, radio **8px**, sombra apenas perceptible (`0 1px 3px rgba(0,0,0,0.05)`), padding **16px**. Encabezado de tarjeta: título en Inter 600 de 16px con un ícono de línea en color primario. Al hacer hover, la sombra sube a `0 2px 8px rgba(0,0,0,0.10)` — el único "movimiento" permitido en las superficies.

Las **KPI-cards** del dashboard añaden: etiqueta en caption 12px, valor grande (28px, 700) y opcionalmente un delta con semáforo. El valor siempre en `--rutx-text` salvo el KPI "Total" que usa `--rutx-accent`.

#### Tablas

El componente `<x-data-table>` es el pilar de la plataforma (los reportes son tablas el 70 % del tiempo). Especificación única para todas las tablas del sistema:

| Elemento | Especificación |
|----------|----------------|
| Encabezado | Fondo `--rutx-bg` con texto `--rutx-primary` en 12px 600, mayúsculas con tracking; columnas ordenables con flecha al hacer hover |
| Filas | Padding 10px 16px, altura 48px, borde inferior 1px `--rutx-border`; filas alternas en `--rutx-surface-grey` |
| Hover | La fila se tiñe con `--rutx-secondary` al 10 % |
| Celdas numéricas | JetBrains Mono 14px, alineadas a la derecha |
| Celdas de estado | `<x-status-badge>` (texto + fondo 12 % de opacidad; nunca color solo) |
| Acciones | Columna fija a la derecha con menú de acciones (iconos de 20px, tooltip) |
| Pie | Fila de totales en negrita con fondo `--rutx-accent-light`; paginación estándar (25 filas) |
| Estado vacío | Ilustración mínima + mensaje "Sin registros para este período" con el filtro que lo causó |

#### Botones

| Tipo | Apariencia |
|------|------------|
| Primario (Consultar, Guardar, Enviar aviso) | Fondo `--rutx-accent`, texto blanco, radio 12px, altura 48px |
| Secundario (Limpiar, Detalle) | Borde `--rutx-border-accent`, texto `--rutx-accent`, fondo `--rutx-accent-light` en hover |
| Danger (Cancelar venta, Eliminar) | Borde `--rutx-status-error`, texto `--rutx-status-error`; en hover invierte a fondo rojo con texto blanco |
| Icono | Cuadrado 40px, ícono 20px, color primario |

#### Inputs y selectores

Fondo `--rutx-surface-grey`, borde 1px `--rutx-border`, radio 8px, altura 44px, hint en `--rutx-text-muted`. Focus: borde `--rutx-primary` de 2px. Los selectores de fecha/ruta/zona de la barra de filtros siguen exactamente este estilo.

#### Modales y confirmaciones

Centrado, overlay con fondo `rgba(32,41,50,0.45)`, radio 12px, ancho máximo 560px. El modal de cancelación de venta es obligatorio en el flujo correspondiente e incluye el campo de motivo marcado como requerido y las políticas aplicables.

#### Gráficas

Paleta centralizada (sección 3.2), fondo `--rutx-chart-blue-bg`, línea principal en `--rutx-chart-blue`, ejes en `--rutx-border`, tooltips con tarjeta blanca. Chart.js instalado vía npm (nunca CDN).

#### Mapa

Leaflet con tiles neutros (sin colores saturados), marcadores en primario para clientes, naranja solo para la posición en vivo del vendedor, y línea de recorrido con trazo primario semitransparente. La leyenda y los controles usan las tarjetas estándar.

---

## 4. Componentes base (los arquetipos del sistema)

Cada componente vive en `resources/views/components/` con su clase PHP en `App\View\Components`, estilos vía tokens y documentación de props en el propio archivo. La lista mínima para cubrir todas las pantallas del MVP:

| Componente | Arquetipo que resuelve |
|------------|------------------------|
| `<x-data-table>` | Toda tabla de datos (reportes, listados, catálogos) |
| `<x-kpi-card>` | Indicadores del dashboard |
| `<x-filter-bar>` | Barra de filtros estándar con Consultar/Limpiar |
| `<x-date-range>` | Selector de rango de fechas (diario/semanal/mensual/personalizado) |
| `<x-chart>` | Gráfica con paleta centralizada (línea, barras, donut) |
| `<x-status-badge>` | Estados de negocio con el mapeo de la app móvil |
| `<x-currency>` | Formato MXN en JetBrains Mono |
| `<x-modal>` + `<x-confirm-dialog>` | Diálogos y confirmaciones destructivas |
| `<x-alert>` | Mensajes de éxito/advertencia/error en fondos pastel |
| `<x-loading-state>` | Estado de carga y refresco de componentes Livewire |
| `<x-map-view>` | Contenedor Leaflet con leyenda estándar |
| `<x-notification-bell>` | Bandeja de notificaciones en el header |
| `<x-breadcrumb>` y `<x-page-header>` | Encabezado estándar de pantalla (sección 2.4) |

> Regla de consistencia: **antes de crear cualquier elemento nuevo, se revisa esta lista**. Si un caso no encaja en ningún componente existente, se propone la extensión del componente (nunca un componente hermano "parecido").

---

## 5. Flujo de datos y vínculo con el contrato API web

Sin migraciones ni Eloquent, cada componente Livewire usa **solo** `App\Services\ApiClient`. El cliente obtiene el JWT web desde la sesión cifrada de Laravel, usa HTTPS, aplica timeout/reintentos acotados y traduce el envelope de error de la API a mensajes seguros. El navegador nunca recibe el JWT, nunca llama al Sincronizador directamente y nunca conoce credenciales de Firebird o de instalación.

| Familia de contrato web | Pantallas/componentes que la consumen | Fuente de detalle |
|---|---|---|
| `/api/v2/web/auth/*` | Login, recuperación de identidad y cierre de sesión. | `Contrato_API_Web_V2.md` §5. |
| `/api/v2/web/dashboard` | `<x-kpi-card>`, `<x-chart>`, tabla de movimientos. | `Contrato_API_Web_V2.md` §6.1. |
| `/api/v2/web/agendas/*` | Calendario Ruta · Agenda, franjas de vendedor y acordeón de clientes. | `Contrato_API_Web_V2.md` §6.2. |
| `/api/v2/web/route-monitor/*` | `<x-map-view>`, jornada, visitas y última venta. | `Contrato_API_Web_V2.md` §6.1. |
| `/api/v2/web/sales/*` y `/reports/*` | Visor, detalle, cancelación, tablas y comparativas. | `Contrato_API_Web_V2.md` §6.3. |
| `/api/v2/web/customers/*`, `/inventory/*`, `/notifications/*` | Catálogos, inventario por ruta y avisos. | `Contrato_API_Web_V2.md` §6.4. |

> **Límite no negociable:** `localhost:5047/api/v2/admin/*` no figura en ninguna pantalla ni en ninguna variable de RutX Web. Es un administrador técnico local para instalar, configurar y diagnosticar el Sincronizador en la PC del cliente; su ciclo de vida pertenece al instalador, no al portal administrativo.

El intervalo de polling del monitoreo se declara explícitamente en el componente Livewire (10–30 s). Si un endpoint posterior ofrece SSE, se evalúa como mejora de transporte sin modificar los arquetipos de mapa, cards ni tablas.

---

## 6. Relación con el plan de sprints

Este documento **no duplica calendarios de trabajo**. La secuencia diaria, alcance y criterios de cierre viven en [`Plan_Sprints_Semana_1.md`](Plan_Sprints_Semana_1.md). Para construir una pantalla se consulta esta matriz:

| Si vas a construir... | Lee primero | Después implementa |
|---|---|---|
| Tokens, tipografía, cards, etiquetas, botones o tablas | §3–4 de este plan y `DesarrolloSeguroconLaravel12.md` §7 | `tokens.css`, arquetipo Blade y playground. |
| Topbar, sidebar o nueva vista | §2 de este plan | `config/navigation.php`, `<x-app-topbar>`, `<x-app-sidebar>` y ruta del módulo. |
| Pantalla con datos | §2.4 y §4 de este plan + contrato API | Livewire + `ApiClient`; nunca datos dentro de Blade. |
| Consulta o comando nuevo | `Contrato_API_Web_V2.md` + `guidelines.md` §1 | DTO/servicio/controlador bajo `Web/` en el Sincronizador. |
| Cambio de alcance | `Plan_Ejecucion_Web_RutX.md` + plan de sprints | Actualización documental antes del código. |

---

## 7. Criterios de éxito

La plataforma se considera bien construida en lo estructural y visual cuando: **(a)** cualquier pantalla nueva se construye en un día o menos ensamblando arquetipos existentes; **(b)** un cambio de marca se resuelve editando un solo archivo; **(c)** la demo al cliente muestra la misma "mano" visual en todas las pantallas; y **(d)** ningún desarrollador necesita decidir colores, tamaños o tipografía: todo está decidido en este documento y en `guidelines.md`.

---

*Documento complementario: [`Mapa_Documental_RutX_Web.md`](Mapa_Documental_RutX_Web.md) define la jerarquía entre documentos; [`guidelines.md`](guidelines.md) rige seguridad y mantenimiento; y [`Contrato_API_Web_V2.md`](Contrato_API_Web_V2.md) define los datos que consumen estos componentes.*
