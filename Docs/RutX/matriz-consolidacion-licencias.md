# Matriz de consolidación — Licenciamiento y Hub RutX

Este documento establece las decisiones que prevalecen sobre borradores anteriores. Su objetivo es impedir que la app, el Sincronizador y Plataforma RutX implementen topologías incompatibles.

| Tema | Alternativas consideradas | Decisión definitiva | Razón |
|---|---|---|---|
| LicenseKey | Incluir cupos/funciones en clave o usar identificador estable. | `LICX-434F-5941-0001` estable; reglas en revisión firmada. | Renovar o cambiar cupo no cambia identidad comercial. |
| Firma | Cifrado sin firma o firma asimétrica. | Ed25519 sobre payload canónico. | Los consumidores verifican el mismo documento; clave privada sólo en RutX. |
| Dominios | Subdominio por cliente, por instalación o API única. | `plataform.rutx.quest` para RutX y `rutx.quest/api/v1` para clientes, app y Hub. | Una configuración móvil para todos. |
| Destino de ventas | IP LAN, doble endpoint, túnel con hostname por PC o Relay central. | Hub/Relay central por `syncInstanceId` en token de dispositivo. | Sin IP, DNS por cliente o configuración manual. |
| Puerto 5047 | Puerto WAN abierto o servicio interno. | Puerto/proceso interno; no expuesto a router ni teléfono. | La conexión persistente sale desde la PC. |
| QR | URL de PC o token de canje. | Token temporal de un uso con identidad de cliente/licencia/instalación. | Vincula sin exponer secretos ni topología de red. |
| App móvil | API distinta por cliente o API única. | Sólo `https://rutx.quest/api/v1`. | APK única; el QR enrola, no configura URL. |
| Autorización de ruta | `syncInstanceId` enviado sin validar. | Claims de token: `clientId`, `syncInstanceId`, `deviceId`; Hub los valida. | Previene que un dispositivo se dirija a otro cliente. |
| Ventas sin red | Bloquear venta o persistir local. | Outbox durable con `operationId`/`idempotencyKey`. | No se pierden ventas ni se duplican reintentos. |
| Estado del Sincronizador | App conecta directo o Relay saliente. | Sesión Relay saliente persistente y presencia en Hub. | No expone la red del cliente. |
| Base local | Modificar Microsip o extender Extras. | `Extras.fdb` para licencia, dispositivos, QR y operaciones Relay. | Microsip queda intacto. |
| Teléfono perdido | Baja inmediata central o orden aplicada localmente. | Orden firmada entregada por Relay y aplicada/auditada por Sincronizador. | Respeta periodos sin conexión. |

> Si un documento anterior incluye `lanEndpoint`, `syncEndpoint`, una IP `192.168.*` o `sync-{cliente}-{instalacion}.rutx.quest`, ese modelo queda descartado. Prevalecen el contrato maestro y esta matriz.

