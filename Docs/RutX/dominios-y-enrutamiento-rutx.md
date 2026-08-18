# Dominios, API y enrutamiento — RutX Hub/Relay

**Estado:** decisión arquitectónica definitiva para el MVP de comunicaciones remotas.  
**Alcance:** documento técnico; no configura DNS ni servicios.

## Idea general

RutX opera con **dos dominios**. La aplicación móvil utiliza un solo API base, sin importar a qué cliente, sucursal o Sincronizador pertenezca. La asignación se hace por credenciales y *claims* emitidos después del QR, no por DNS ni por una dirección guardada manualmente en cada teléfono.

> La venta viaja siempre a `rutx.quest/api/v1`. El Hub identifica la instalación destino y la entrega al Sincronizador mediante su sesión Relay saliente autenticada.

## Catálogo de dominios

| Dirección | Usuarios | Responsabilidad |
|---|---|---|
| `https://plataform.rutx.quest` | Personal RutX | Clientes, licencias, instalaciones, QR, presencia de Sincronizadores y auditoría. |
| `https://rutx.quest` | Clientes, apps y Sincronizadores | Portal cliente y Hub/API central compartido. |
| `https://rutx.quest/api/v1` | Todas las apps móviles | Enrolamiento, login, ventas, rutas, cierres y acuses. |
| `wss://rutx.quest/api/v1/relay/connect` | Todos los Sincronizadores | Sesión Relay saliente y persistente de cada PC. |

No existe `sync-{cliente}-{instalacion}.rutx.quest`, ni una URL por cliente, ni una IP de cliente dentro de la app.

## Qué distingue a cada operación

| Identidad | Rol | Regla |
|---|---|---|
| `clientId` | Empresa/tenant | El Hub y el Sincronizador lo verifican en cada operación. |
| `licenseId` | Contrato y reglas firmadas | Estable y distinto de secretos. |
| `syncInstanceId` | PC/Sincronizador concreto | Lo declara el Relay y lo usa Hub para entregar trabajo. |
| `deviceId` | Teléfono enrolado | Ocupa un cupo y tiene clave de dispositivo. |
| `userId` | Operador humano | Tiene permisos dentro del cliente y dispositivo. |
| `operationId` / `idempotencyKey` | Operación de negocio | Impide aplicar dos veces una venta reintentada. |

La app no debe enviar un `syncInstanceId` arbitrario como autoridad. Tras enrolarse recibe un token de dispositivo firmado cuyos *claims* incluyen `clientId`, `syncInstanceId` y `deviceId`; el Hub usa esos *claims* para enrutar y rechaza diferencias entre el token y el cuerpo.

## QR: vínculo seguro, no configuración de red

El QR se genera en la PC, se registra en el Hub y contiene únicamente el contexto necesario para iniciar un canje de un solo uso:

```json
{
  "v": 1,
  "qrId": "qr_01J8C8H7YEXAMPLE",
  "clientId": "clt_000001",
  "licenseId": "LICX-434F-5941-0001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "enrollmentToken": "token-aleatorio-unico",
  "expiresAt": "2026-08-16T23:30:00Z"
}
```

Al escanearlo, la app llama siempre a `POST https://rutx.quest/api/v1/mobile/enroll/claim`. El Hub valida el token con la instalación conectada, asigna el cupo y emite una credencial de dispositivo. No se guarda host, puerto o IP.

## Cómo llega una venta al destino correcto

```text
App móvil
  └── POST https://rutx.quest/api/v1/mobile/sales
        └── Hub: valida device token y resuelve syncInstanceId
              └── Relay de syn_coyatoc_matriz_01
                    └── Sincronizador aplica en Microsip y responde acuse
```

El Hub guarda el estado de entrega. Si el Sincronizador está desconectado, informa `SYNC_OFFLINE`; la app conserva localmente la misma operación e intenta de nuevo con la misma `idempotencyKey`. Si el teléfono se queda sin red, el flujo es igual: la venta queda en su cola local.

## Puerto 5047

El puerto `5047` puede seguir siendo el puerto interno del proceso de Sincronizador o de un adaptador local, pero **no se expone a Internet ni se configura en móviles**. La PC abre una conexión saliente TLS/`wss` al Hub y recibe por esa sesión las operaciones que le corresponden. Por tanto, no hay *port-forwarding*, DNS de instalación ni IP fija de módem.

## Flujo administrativo de un teléfono

| Paso | Actor | Resultado |
|---:|---|---|
| 1 | RutX en Plataforma | Crea cliente, licencia e instalación. |
| 2 | Técnico | Activa el Sincronizador con código único; este abre Relay saliente. |
| 3 | Sincronizador | Genera QR y registra hash de token en el Hub. |
| 4 | Teléfono | Escanea QR y canjea contra `rutx.quest/api/v1`. |
| 5 | Hub + Sincronizador | Validan token/cupo y emiten `deviceId` + token de dispositivo. |
| 6 | Usuario | Inicia sesión contra API central usando su dispositivo enrolado. |
| 7 | Hub | Enruta cada venta al Relay de la instalación declarada en los *claims*. |

## Resumen operativo

```text
plataform.rutx.quest     → vendedores y soporte RutX
rutx.quest               → clientes
rutx.quest/api/v1        → todas las apps móviles
Relay saliente            → cada Sincronizador, identificado por syncInstanceId
```

