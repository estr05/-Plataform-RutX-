# Especificación Visual y Documental — Día 1

**Fecha:** 13 de agosto de 2026
**Estado:** Implementación parcial (fundación en código creada; cierre final pendiente del Día 2).
**Ámbito:** Definición de los contratos visuales, de navegación y de infraestructura base para RutX Web.

Este documento consolida las especificaciones cerradas del Día 1. Su implementación en código ha iniciado (revisar archivos creados como `tokens.css`, `app.css`, layout y componentes) y sirve como base técnica.

---

## 1. Matriz de Tokens Aprobada

Los siguientes tokens formarán parte exclusiva de `resources/css/tokens.css`. Todo color o medida en la aplicación debe referenciar a estas variables. No se permitirán colores hexadecimales en las vistas.

### Paleta Semántica
- `--rutx-primary`: `#003B5C` (Header, sidebar, encabezados de tabla, enlaces activos)
- `--rutx-primary-dark`: `#002D47` (Hover primario, footer)
- `--rutx-accent`: `#FF6A13` (Botones primarios, totales destacados, menú activo)
- `--rutx-accent-light`: `#FFF3E0` (Hover secundarios, fondos suaves)
- `--rutx-secondary`: `#A8C8E9` (Fondos secundarios, marcas gráficas)
- `--rutx-bg`: `#F4F6F8` (Superficie metálica/fondo general)
- `--rutx-surface`: `#FFFFFF` (Tarjetas, paneles, inputs)
- `--rutx-surface-grey`: `#F5F5F5` (Filas alternas de tabla, deshabilitados)
- `--rutx-text`: `#343D45` (Texto principal)
- `--rutx-text-muted`: `#6B7680` (Etiquetas, hints)
- `--rutx-border`: `#E0E0E0` (Bordes generales)
- `--rutx-status-success`: `#2E7D32` (Estado OK)
- `--rutx-status-warning`: `#E65100` (Pendiente)
- `--rutx-status-error`: `#C62828` (Error/Cancelado)
- `--rutx-status-unknown`: `#9E9E9E` (Sin conexión)

### Subpaleta de Gráficas
- `--rutx-chart-blue`: `#005691`
- `--rutx-chart-cyan`: `#0277BD`
- `--rutx-chart-blue-bg`: (Variante clara de `--rutx-chart-blue`)

### Geometría y Espaciado
- **Radios:**
  - Tarjetas/Inputs: `8px`
  - Botones/Modales: `12px`
- **Alturas de controles:**
  - Botones: `48px`
  - Inputs: `44px`
  - Topbar: `64px`
- **Sombras:**
  - Base (tarjetas): `0 1px 3px rgba(0,0,0,0.05)`
  - Hover (tarjetas): `0 2px 8px rgba(0,0,0,0.10)`
- **Espaciado (Padding):**
  - Tarjetas: `16px`
  - Filas de tabla: `10px 16px`

---

## 2. Especificación de Carga Tipográfica

La tipografía debe cargarse localmente. El uso de CDNs (Google Fonts, etc.) está estrictamente prohibido.

- **Fuente de Interfaz:** `Inter` (formatos `.woff2` en `public/fonts/`).
  - Pesos: 400, 500, 600, 700.
  - Fallback stack: `Inter, system-ui, -apple-system, sans-serif`
- **Fuente de Datos Técnicos/Montos:** `JetBrains Mono` (formatos `.woff2` en `public/fonts/`).
  - Pesos: 400.
  - Fallback stack: `'JetBrains Mono', ui-monospace, SFMono-Regular, monospace`

### Tamaños y Altura de Línea
- H1: 30px, line-height 1.2
- H2: 24px, line-height 1.25
- H3: 20px, line-height 1.3
- Cuerpo/Tablas: 16px, line-height 1.5
- Etiquetas/Badges: 12px, line-height 1.4

---

## 3. Catálogo de Arquetipos

Los siguientes componentes Blade deben construirse. No se creará ninguna pantalla de negocio hasta que estos componentes existan.

| Componente | Responsabilidad | Props Principales / Slots |
|---|---|---|
| `<x-data-table>` | Renderizar tablas de datos con paginación. | Props: `headers` (array), `items` (colección). Slots: `row` (renderizado por fila), `empty` (estado vacío). |
| `<x-kpi-card>` | Mostrar métricas individuales. | Props: `title`, `value`, `delta`, `status` (success, warning, error), `icon`. |
| `<x-filter-bar>` | Contener los filtros de pantalla. | Slots: inputs de filtro, botones de acción. |
| `<x-date-range>` | Selección de rango de fechas. | Props: `startDate`, `endDate`, `presets` (boolean). |
| `<x-status-badge>` | Renderizar píldoras de estado visuales. | Props: `status` (success/warning/error/unknown), `label`. |
| `<x-currency>` | Renderizar valores en MXN con JetBrains Mono. | Props: `amount` (numeric). Alineado a la derecha. |
| `<x-chart>` | Renderizar gráficas de Chart.js. | Props: `type` (line, bar), `data` (JSON array), `labels`. |
| `<x-modal>` | Contenedor para overlays. | Props: `title`, `isOpen`. Slots: contenido, footer. |
| `<x-confirm-dialog>` | Específico para acciones destructivas. | Props: `title`, `message`, `confirmText`, `danger` (boolean). Slots: `reasonInput` (para motivo). |
| `<x-alert>` | Mensajes de notificación in-line. | Props: `type` (success/warning/error), `message`. |
| `<x-loading-state>`| Overlay o spinner de estado de red. | Props: `target` (Livewire target), `message`. |
| `<x-map-view>` | Contenedor Leaflet. | Props: `markers`, `center`, `zoom`. |
| `<x-notification-bell>`| Campana de avisos en topbar. | Props: `unreadCount`. |
| `<x-breadcrumb>` | Navegación de jerarquía. | Props: `paths` (array). |
| `<x-page-header>`| Título H1 y acciones primarias de vista. | Props: `title`. Slots: acciones. |

---

## 4. Contrato de Navegación

Toda la navegación existirá en un solo archivo: `config/navigation.php`. Tendrá este esquema (schema) obligatorio:

```php
return [
    'modules' => [
        'venta' => [
            'label' => 'Venta',
            'icon' => 'shopping-cart',
            'permission' => 'module.venta.access',
            'default_route' => 'venta.reportes',
            'views' => [
                'venta.reportes' => [
                    'label' => 'Reportes y Gráficas',
                    'icon' => 'chart-bar',
                    'permission' => 'reports.read'
                ],
                // ... otras vistas
            ]
        ],
        // ... otros módulos
    ]
];
```

Los componentes `<x-app-topbar>` y `<x-app-sidebar>` leerán exclusivamente de esta estructura y no duplicarán condicionales ni permisos.

---

## 5. Matriz de Frontera y Configuración (Variables de Entorno)

Las únicas variables de entorno permitidas para la conexión con el backend son:

- `API_WEB_BASE_URL` (Debe ser `https://`)
- `API_WEB_AUTH_URL`
- `API_WEB_TIMEOUT`
- `API_WEB_CONNECT_TIMEOUT`
- `API_WEB_CLIENT_ID`
- `API_WEB_CLIENT_SECRET`
- `API_WEB_VERIFY_TLS` (Obligatorio a `true` en producción)

**Reglas de Frontera y Exclusión:**
- **Prohibido:** Configurar URLs que apunten a `/api/v1/*` (API móvil).
- **Prohibido:** Configurar URLs que apunten a `localhost:5047/api/v2/admin/*`.
- La sesión web utilizará el driver `cookie` o `file` nativo de Laravel de forma cifrada. El JWT devuelto por `/api/v2/web/auth/login` se almacenará en sesión y nunca será expuesto al `localStorage` o al cliente JavaScript.

---

## 6. Checklist de Salida hacia el Día 2

Para dar inicio al Día 2, el equipo debe confirmar que se construirán estrictamente los siguientes elementos de infraestructura:
- [ ] Inicialización de repositorio Laravel (Tailwind 4, Vite).
- [ ] Incorporación de archivo `.env.example` con la matriz de frontera aprobada.
- [ ] Incorporación de las fuentes `.woff2` locales.
- [ ] Creación de `resources/css/tokens.css` y vinculación en `app.css`.
- [ ] Configuración de Laravel Pint, Lint y script de build en CI.
- [ ] Auditoría automatizada anti-hex: script que comprueba `resources/views` y `resources/css` excluyendo `tokens.css`.
- [ ] Creación del `config/navigation.php`.
- [ ] Creación de la estructura del layout (`app.blade.php`), `<x-app-topbar>` y `<x-app-sidebar>`.
