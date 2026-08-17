# Red del Sincronizador y puerto 5047 — Modelo Hub/Relay

## Decisión final

El teléfono no abre una conexión directa a `:5047` de la PC del cliente. Todas las apps móviles hablan con `https://rutx.quest/api/v1`. El Sincronizador abre una conexión **saliente** persistente hacia ese Hub, y RutX le entrega las solicitudes que le corresponden.

El puerto `5047` queda como puerto interno del proceso del Sincronizador, útil para sus componentes locales si la implementación lo requiere. No se configura en teléfonos, no requiere IP fija, no se expone por DNS y no se abre en el router del cliente.

| Comunicación | Origen | Destino | Dirección |
|---|---|---|---|
| Conexión Relay | Sincronizador | `wss://rutx.quest/api/v1/relay/connect` | Saliente desde PC. |
| Chequeo de licencia | Sincronizador | `https://rutx.quest/api/v1/installations/license/check` | Saliente desde PC. |
| Venta / login / rutas | App móvil | `https://rutx.quest/api/v1/mobile/...` | Saliente desde teléfono. |
| Aplicación local | Sincronizador | Microsip + `Extras.fdb` | Local a la PC. |

## Firewall y router

| Elemento | Decisión |
|---|---|
| Router del cliente | No crear *port-forwarding*, DMZ ni regla WAN para `5047`. |
| IP pública | No es necesaria. |
| IP privada de la PC | Puede cambiar; no se registra en la app. |
| Firewall Windows | Permitir al ejecutable del Sincronizador iniciar conexiones salientes TLS; no exponer el puerto al exterior. |
| Certificados | TLS obligatorio entre móvil ↔ RutX y Sincronizador ↔ RutX. |

## Cuando no hay conectividad

La ausencia de Internet no impide capturar ventas. La app conserva la operación localmente y no la marca como enviada. Cuando recupera red, usa el mismo `rutx.quest/api/v1` y el Hub reintenta su entrega sólo mientras el Sincronizador mantenga sesión. Si la PC también está sin Internet, la app sigue conservando su cola hasta recibir un acuse final.

> El puerto `5047` sigue siendo parte del Sincronizador, pero dejó de ser una dirección que tenga que conocer un teléfono o un administrador.

