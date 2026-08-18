# Requerimientos mínimos — Plataforma RutX y Hub/Relay

## Propósito

`https://plataform.rutx.quest` será la interfaz exclusiva de RutX para operar clientes, licencias, instalaciones y soporte. `https://rutx.quest/api/v1` expone la API multiempresa y el Hub/Relay. Pueden ser el mismo proyecto Laravel con rutas y middleware separados.

## 1. Entidades mínimas

| Entidad | Campos mínimos | Regla |
|---|---|---|
| `clients` | `id`, `code`, `legal_name`, `display_name`, `status`, timestamps | `code` e `id` únicos. |
| `licenses` | `license_id`, `client_id`, `status`, fechas, gracia, límite de móviles, permisos, revisión | `license_id` único; una revisión firmada por versión. |
| `sync_instances` | `id`, `client_id`, `name`, `status`, `activation_state`, `last_seen_at`, `current_connection_id` | `id` único globalmente; una instalación pertenece a un cliente. |
| `installation_credentials` | `sync_instance_id`, hash de secreto, `key_id`, emitido/revocado/en uso | Nunca guardar el secreto en texto plano. |
| `device_bindings` | `device_id`, `sync_instance_id`, clave pública, número de dispositivo, estado, altas/bajas | Índice único por `device_id`; número único dentro de instalación. |
| `enrollment_tokens` | hash, `sync_instance_id`, uso, expiración, consumido por, timestamps | Un solo uso y vencimiento breve. |
| `relay_connections` | `connection_id`, `sync_instance_id`, conectado/desconectado, versión, latencia | Es presencia técnica, no fuente comercial. |
| `relay_deliveries` | `request_id`, `operation_id`, destino, estado, reintentos, acuse | Idempotencia y trazabilidad de entrega. |
| `audit_events` | actor, tipo, entidades, metadatos mínimos, UTC | Inmutable para investigación operativa. |

## 2. Módulos mínimos en Plataforma RutX

| Módulo | Operaciones requeridas | Resultado esperado |
|---|---|---|
| Clientes | Alta, edición, suspensión, consulta de instalaciones | Crear el tenant correcto y evitar ventas a cliente inactivo. |
| Licencias | Emitir, renovar, suspender, revocar, emitir revisión firmada | Controlar derechos sin alterar la LicenseKey. |
| Instalaciones | Crear `syncInstanceId`, activar, ver versión, última conexión, deshabilitar | Relacionar una PC con un cliente y observar su estado. |
| QR de dispositivos | Solicitar generación, ver cupos, expirar, auditar canje | Vincular teléfonos sin captura manual. |
| Inventario de dispositivos | Ver activos, deshabilitar perdido, liberar/reemplazar | Aplicar límites de licencia. |
| Relay | Estado online/offline, operaciones en tránsito, acuses y errores | Soporte sin acceder a la red del cliente. |
| Auditoría | Filtro por cliente, licencia, instalación, dispositivo y actor | Evidencia de decisiones y operaciones. |

## 3. API mínima del Hub

| Ruta | Autenticación | Consumidor | Finalidad |
|---|---|---|---|
| `POST /installations/activate` | Código de activación | Sincronizador nuevo | Intercambiar código por credencial de instalación. |
| `GET /relay/connect` | Credencial de instalación | Sincronizador | Mantener sesión Relay saliente. |
| `POST /installations/license/check` | Credencial de instalación | Sincronizador | Obtener revisión firmada vigente. |
| `POST /mobile/enroll/claim` | Token QR | App móvil | Reservar/activar un dispositivo por relay. |
| `POST /mobile/auth/login` | Token de dispositivo | App móvil | Autenticar usuario humano dentro de instalación. |
| `POST /mobile/sales` | Sesión usuario-dispositivo | App móvil | Reenviar venta al Sincronizador correcto. |
| `POST /mobile/operations/{id}/ack` | Sesión usuario-dispositivo | App móvil | Consulta/reconciliación de una operación. |

## 4. Reglas de enrutamiento que el Hub debe imponer

1. El token de dispositivo debe incluir `clientId`, `syncInstanceId`, `deviceId`, estado y vencimiento.
2. La sesión de usuario debe estar asociada al mismo `deviceId` y conservar su `syncInstanceId` en los claims.
3. Para una venta, el Hub toma el destino exclusivamente de esos claims; ignora cualquier `clientId` o `syncInstanceId` enviado en el cuerpo que no coincida.
4. El Hub solo entrega al Sincronizador con sesión activa cuyo `syncInstanceId` sea exactamente el autenticado en la conexión Relay.
5. El Sincronizador verifica de nuevo dispositivo, licencia, usuario, permiso e idempotencia antes de aplicar el cambio en Microsip.

## 5. Estados mínimos

| Recurso | Estados |
|---|---|
| Cliente | `active`, `suspended`, `closed` |
| Licencia | `active`, `grace`, `suspended`, `revoked`, `expired` |
| Instalación | `pending`, `online`, `offline`, `disabled` |
| Dispositivo | `pending`, `active`, `disabled`, `released` |
| Entrega Relay | `received`, `forwarded`, `acknowledged`, `rejected`, `sync_offline`, `expired` |

## 6. Requerimiento de ejecución persistente

El Hub/Relay necesita conservar conexiones de Sincronizador y recibir eventos mientras existan PCs conectadas. Antes de construirlo se debe definir una estrategia de proceso persistente, presencia compartida y reconexión; un API que duerme entre solicitudes no debe ser la única pieza de presencia Relay. Esta es una decisión de despliegue posterior, no un requisito para crear las tablas, contratos y módulos de Plataforma RutX.

## 7. Criterios mínimos de aceptación

| Escenario | Debe ocurrir |
|---|---|
| QR de Coyatoc reclamado por teléfono nuevo | RutX identifica la instalación Coyatoc Matriz, reserva un cupo y emite `deviceId`. |
| Venta desde otra ciudad | App la manda a la misma API; Hub entrega exclusivamente a Coyatoc Matriz. |
| App sin datos | Venta queda durable en la cola móvil; no se pierde ni se marca enviada. |
| Sincronizador desconectado | Hub responde `SYNC_OFFLINE`; app conserva la operación. |
| Reintento de misma venta | Sincronizador devuelve `DUPLICATE` o acuse anterior, sin crear doble venta. |
| Dispositivo perdido | Hub rechaza sus tokens desde la baja y la instalación no acepta nuevas operaciones. |

