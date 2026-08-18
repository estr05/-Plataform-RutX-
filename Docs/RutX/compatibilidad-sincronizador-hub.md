# Compatibilidad del Sincronizador con `rutx.quest/api/v1`

**Estado:** decisión de integración documental.  
**Revisión realizada:** repositorio `RutX-Sincronizador`, rama `dev`, commit `400312c57cf7ac00c9e6597dd5925135a031648b`.  
**Alcance:** este documento no modifica el Sincronizador, la app Flutter, Laravel, Firebird ni la infraestructura.

## Respuesta directa

**Sí:** el sitio web de clientes y las apps móviles pueden vivir bajo el mismo dominio público, `https://rutx.quest`, y compartir el prefijo técnico `https://rutx.quest/api/v1`.

**No:** no deben usar la misma ruta ni el mismo controlador para propósitos distintos. La separación correcta se hace por prefijos de recurso y autenticación, no por un subdominio o una URL por cliente. El cliente y la instalación se derivan de la sesión autenticada, nunca de un destino que el navegador o la app puedan editar.

> `rutx.quest` identifica el producto público de RutX. La ruta identifica al consumidor; el token autenticado determina el `clientId`, el `syncInstanceId` y, cuando aplique, el `deviceId`.

| Consumidor | Dirección propuesta | Autenticación | Destino real |
|---|---|---|---|
| Portal web de un cliente | `https://rutx.quest/` y `https://rutx.quest/api/v1/client/*` | Sesión de usuario/administrador del cliente | Laravel central de RutX. |
| App Flutter | `https://rutx.quest/api/v1/mobile/*` | Credencial del dispositivo más sesión de usuario | Hub/Relay; se reenvía al Sincronizador correcto. |
| Sincronizador | `https://rutx.quest/api/v1/installations/*` y `wss://rutx.quest/api/v1/relay/connect` | Credencial de instalación | Laravel/Hub central. |
| Personal RutX | `https://plataform.rutx.quest/` y su API interna | Rol interno RutX | Laravel central, separado por dominio y roles. |
| Panel técnico de una PC | Solo dentro de la red controlada de esa PC, puerto `5047` | Administración local reforzada | Proceso .NET del Sincronizador; no es el portal remoto del cliente. |

## Qué existe hoy en el Sincronizador

El proceso .NET ya expone controladores ASP.NET Core y, por defecto, escucha en el puerto `5047` en todas las interfaces (`0.0.0.0`). También publica `/admin` como archivo estático y mapea todos los controladores registrados. [1]

Las rutas móviles implementadas ya emplean mayoritariamente el prefijo local `/api/v1`. Por tanto, **no hay una incompatibilidad de versión** con el Hub propuesto. Lo que cambia es el punto de entrada: la app dejará de llamar directamente a `PC:5047` y llamará al Hub común; el Hub enviará el comando por la conexión Relay saliente a la instalación correspondiente.

| Función | Ruta local implementada | Observación de compatibilidad |
|---|---|---|
| Login nativo Microsip | `POST /api/auth/login` | Es la excepción: no lleva `/v1`. Devuelve un JWT local de 12 horas con los claims del vendedor, caja y almacén. [2] |
| Identidad de usuario | `GET /api/auth/me` | Sigue siendo una responsabilidad local del Sincronizador. [2] |
| Sincronización matutina | `GET /api/v1/routes/sync` | Ya se resuelve por los claims del JWT, no por un identificador de vendedor en la URL. [3] |
| Cierre de ruta | `POST /api/v1/routes/close` | Compatible como operación Relay; se conserva su validación actual. [3] |
| Venta Punto de Venta | `POST /api/v1/pv/ventas` | Ya tiene idempotencia mediante `venta_movil_id`; la capa Hub debe conservar dicha semántica. [4] |
| No venta | `POST /api/v1/pv/noventa` | Operación Relay adicional con carga `multipart/form-data`. [4] |
| Alta de cliente en Microsip | `POST /api/v1/clientes` | Ruta protegida; no se confunde con la administración de clientes/contratos de RutX. [5] |
| Administración técnica local | `GET/POST /api/v2/admin/*` y `/admin` | Es un panel local del Sincronizador, separado deliberadamente de la API móvil. [6] |

El archivo `Docs/CONTRATOS.md` del repositorio contiene nombres anteriores como `/api/v1/sync/morning`, `/api/v1/ventas` y `/api/v1/sync/closing`; estos no aparecen como atributos de ruta en los controladores revisados. Para una implementación nueva, **el código y sus pruebas son la referencia operativa**, y ese documento debe tratarse como contrato histórico hasta actualizarlo. [7] [3] [4]

## Frontera de API recomendada

La API pública no debe publicar el puerto `5047` ni copiar literalmente los controladores .NET al borde de Internet. Debe presentar una superficie estable y explícita, con un espacio `mobile` que evita colisiones con el portal web y con las conexiones de instalación.

| API pública en `rutx.quest` | Comando Relay interno sugerido | Operación local existente | Regla |
|---|---|---|---|
| `POST /api/v1/mobile/enroll/claim` | `device.enroll.claim` | Nuevo manejador en el Sincronizador + `Extras.fdb` | El QR se canjea una vez; no expone endpoint local. |
| `POST /api/v1/mobile/auth/login` | `mobile.auth.login` | `POST /api/auth/login` / `FirebirdAuthService` | El Hub conoce el dispositivo y la instalación antes de transmitir las credenciales. |
| `GET /api/v1/mobile/routes/sync` | `mobile.routes.sync` | `GET /api/v1/routes/sync` / `RouteService` | Conserva los claims de usuario y la respuesta de catálogos. |
| `POST /api/v1/mobile/pv/ventas` | `mobile.pv.ventas.create` | `POST /api/v1/pv/ventas` / `VentaServicePv` | `Idempotency-Key` del Hub y `venta_movil_id` local deben identificar la misma operación lógica. |
| `POST /api/v1/mobile/pv/noventa` | `mobile.pv.noventa.create` | `POST /api/v1/pv/noventa` | El Relay debe aceptar adjuntos de forma controlada o usar carga previa con referencia. |
| `POST /api/v1/mobile/routes/close` | `mobile.routes.close` | `POST /api/v1/routes/close` / `RouteService` | Se enruta por sesión de dispositivo, no por un valor recibido en el cuerpo. |
| `POST /api/v1/mobile/clientes` | `mobile.clients.create` | `POST /api/v1/clientes` / `ClienteService` | Es una operación de Microsip; no crea clientes comerciales de RutX. |

Los nombres bajo `/mobile/*` son la **fachada pública estable**. La columna de operación local no obliga a hacer una petición HTTP del Sincronizador hacia sí mismo: el adaptador Relay puede invocar directamente los servicios .NET existentes. Esta opción conserva la lógica validada de Firebird, reduce duplicación y permite retirar gradualmente las rutas directas una vez que todas las apps usen el Hub.

## El punto delicado: dos sesiones, no un JWT compartido

El `AuthController` actual valida usuarios contra Firebird y crea un JWT local firmado con su propia clave. Los controladores móviles locales consumen los claims de ese JWT para obtener vendedor, cajero, caja, almacén y sucursal. [2] [3] [4]

El Hub necesita además un token de sesión propio, enlazado al `deviceId` y al `syncInstanceId`, para saber a qué Relay dirigir cada petición. Por seguridad y mantenibilidad, no deben convertirse en el mismo token ni obligar a Laravel a conocer la clave JWT local de cada PC.

```text
1. App -> Hub: device access token + usuario/contraseña
2. Hub valida el dispositivo y obtiene syncInstanceId desde sus claims.
3. Hub -> Relay: mobile.auth.login
4. Sincronizador valida Firebird y produce su JWT local actual.
5. Hub guarda de forma cifrada la sesión local, asociada a una sesión Hub corta.
6. App recibe solo la sesión Hub.
7. En cada operación, Hub valida su sesión, selecciona el Relay e inyecta
   internamente la sesión local al manejador .NET correspondiente.
```

Así se preservan los claims que las rutas actuales necesitan, la app nunca recibe un secreto de instalación y el Hub puede bloquear de inmediato un `deviceId` dado de baja. La implementación posterior puede reemplazar esta compatibilidad por un manejador Relay nativo, pero no debe romper la separación de responsabilidades.

## Qué significa `web` en el repositorio revisado

El directorio `Controllers/Web` no representa el portal remoto del cliente en `rutx.quest`. Contiene el **panel técnico local** del Sincronizador: sirve `/admin` y usa `/api/v2/admin/*` para configuración, auditoría y diagnósticos de la PC. El propio controlador declara que no tiene autenticación y que se asumía escucha local. [6]

Como el host actual se configura en `0.0.0.0:5047`, esa suposición merece atención antes de cualquier instalación expuesta a una red no confiable. En particular, el endpoint de configuración devuelve y puede actualizar `appsettings.json`; su lectura incluye componentes de la conexión Firebird. [1] [6]

> **Decisión de seguridad:** `api/v2/admin/*` y `/admin` no se publican mediante `rutx.quest`, no se enrutan por el Relay y no se presentan como el portal web del cliente. Antes de uso productivo, su acceso debe limitarse a loopback o una red administrativa controlada y requerir autenticación/autorización adecuada.

El navegador del cliente usará `rutx.quest/` y la API `rutx.quest/api/v1/client/*`; esas rutas son un módulo Laravel central, con sesión del cliente y filtros obligatorios por `clientId`. Para funciones que requieran datos de una instalación concreta, Laravel envía un comando de administración con permiso explícito al Relay, no abre una conexión directa al puerto `5047`.

## Secuencia de adopción sin ruptura

| Etapa | Cambio documental/implementable después | Lo que se conserva |
|---:|---|---|
| 1 | Registrar `syncInstanceId`, credencial de instalación y conexión `wss` saliente. | La API .NET local y los servicios de Firebird. |
| 2 | Agregar un adaptador Relay que traduzca comandos a servicios existentes. | Rutas actuales para pruebas locales y la semántica de idempotencia. |
| 3 | Publicar la fachada `rutx.quest/api/v1/mobile/*`; migrar Flutter al dominio único. | Cola offline de Flutter: elimina una operación solo con acuse. |
| 4 | Implementar portal de cliente bajo `rutx.quest/api/v1/client/*`. | Separación de `plataform.rutx.quest` para el personal RutX. |
| 5 | Restringir el panel local `/admin` y las rutas `api/v2/admin/*`. | Operación técnica local autorizada. |

## Conclusión

La base `https://rutx.quest/api/v1` es compatible con el Sincronizador actual y permite que móviles y portal web compartan un solo dominio público. La compatibilidad no consiste en reenviar tráfico ciegamente al puerto `5047`: consiste en añadir una fachada Hub/Relay que conserva las operaciones y servicios locales, separa las sesiones y enruta por identidad de instalación.

Por tanto, la decisión final es la siguiente:

| Pregunta | Decisión |
|---|---|
| ¿Una sola URL para todas las apps móviles? | **Sí:** `https://rutx.quest/api/v1/mobile/*`. |
| ¿El portal web de clientes puede usar el mismo dominio y versión? | **Sí:** `https://rutx.quest/api/v1/client/*`, con autenticación y permisos propios. |
| ¿`/api/v2/admin` actual sirve como portal remoto de clientes? | **No:** es administración técnica local del Sincronizador. |
| ¿La app debe conocer `IP:5047` o un subdominio de cliente? | **No:** solo conoce `rutx.quest`; el Hub decide el destino. |
| ¿Se reemplazan ahora las rutas .NET existentes? | **No:** se encapsulan detrás del Relay y se migran sin ruptura. |

## Referencias

[1]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Program.cs#L74-L233 "RutX-Sincronizador — configuración HTTP y mapeo de controladores"
[2]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Controllers/Movil/AuthController.cs#L12-L164 "RutX-Sincronizador — autenticación móvil local"
[3]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Controllers/Movil/RouteController.cs#L8-L213 "RutX-Sincronizador — sincronización y cierre de ruta"
[4]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Controllers/Movil/VentasPvController.cs#L10-L283 "RutX-Sincronizador — operaciones Punto de Venta"
[5]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Controllers/Compartidos/ClientesController.cs#L6-L42 "RutX-Sincronizador — alta de clientes en Microsip"
[6]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Controllers/Web/AdminController.cs#L8-L180 "RutX-Sincronizador — panel técnico local"
[7]: https://github.com/estr05/RutX-Sincronizador/blob/400312c57cf7ac00c9e6597dd5925135a031648b/Docs/CONTRATOS.md "RutX-Sincronizador — contratos históricos de API"
