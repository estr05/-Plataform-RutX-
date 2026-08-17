# Contrato técnico — RutX Hub/Relay, licencia y operaciones móviles

**Estado:** arquitectura acordada para implementación.  
**Ejemplo:** Coyatoc, siete dispositivos, permiso de ventas.  
**URL única de todas las apps:** `https://rutx.quest/api/v1`.

## 1. Regla principal

La app móvil nunca configura una IP, `lanEndpoint` o subdominio de cliente. Después de escanear un QR, queda vinculada a una instalación mediante identidad y credenciales. Todas sus peticiones van al Hub central RutX; el Hub identifica el destino y entrega la solicitud al Sincronizador correspondiente por una conexión Relay saliente.

```text
App móvil ── HTTPS ──► rutx.quest/api/v1 ── Relay autenticado ──► Sincronizador correcto
```

La venta se conserva localmente en la app hasta recibir un acuse final. RutX central no escribe directamente en Microsip y no debe convertirse en una segunda base contable.

> **Límite de compatibilidad:** las rutas bajo `/mobile/*` son contratos públicos del Hub, no una exposición directa del puerto `5047`. El adaptador Relay conserva y reutiliza los servicios .NET ya existentes. La relación entre ambas superficies, incluido el JWT local actual, está definida en [Compatibilidad del Sincronizador con Hub](compatibilidad-sincronizador-hub.md).

## 2. Identidades obligatorias

| Campo | Ejemplo Coyatoc | Alcance | Regla |
|---|---|---|---|
| `clientId` | `cli_coyatoc_000001` | Empresa | Lo asigna RutX. |
| `licenseId` | `LICX-434F-5941-0001` | Contrato comercial | Es legible y estable; no es contraseña. |
| `syncInstanceId` | `syn_coyatoc_matriz_01` | Una PC/Sincronizador | Único globalmente. |
| `deviceId` | `dev_01J8C8GQ0EXAMPLE` | Teléfono físico | Ocupa un cupo de la instalación. |
| `userId` | `microsip:ventas:1032` | Operador humano | Tiene roles dentro de la instalación. |
| `operationId` | `op_01J8D1EXAMPLE` | Operación móvil | Identifica una venta o comando. |
| `idempotencyKey` | UUID | Operación + dispositivo | Evita duplicados por reintentos. |

El Hub obtiene `clientId`, `syncInstanceId` y `deviceId` del token autenticado. Aunque una app incluya estos campos en el cuerpo, el Hub no permite que el cuerpo cambie su destino.

## 3. LicenseKey y revisión firmada

La LicenseKey identifica el contrato y no cambia cuando se modifiquen cupos, permisos o fechas:

```text
LICX-434F-5941-0001
```

RutX firma el JSON canónico de cada revisión con Ed25519. El Sincronizador valida la firma antes de aplicar la licencia en `Extras.fdb`.

```json
{
  "schemaVersion": 1,
  "licenseId": "LICX-434F-5941-0001",
  "clientId": "cli_coyatoc_000001",
  "issuedAt": "2026-08-16T00:00:00Z",
  "startsAt": "2026-08-16T00:00:00Z",
  "expiresAt": "2027-08-15T23:59:59Z",
  "offlineGraceDays": 5,
  "maxMobileDevices": 7,
  "permissions": ["sales"],
  "revision": 1,
  "status": "active",
  "keyId": "rutx-ed25519-2026-01"
}
```

```json
{
  "payload": "json-canonico-utf8",
  "signature": "base64url-firma-ed25519",
  "algorithm": "Ed25519",
  "keyId": "rutx-ed25519-2026-01"
}
```

| Estado | Política local |
|---|---|
| `active` y antes de vencimiento | Operación permitida por permisos. |
| Después de vencimiento y dentro de 5 días | Gracia: operación permitida con aviso; no enrolar nuevos dispositivos. |
| Fuera de gracia | Bloquear nuevas ventas; permitir recuperación y sincronización. |
| `suspended` o `revoked` | Bloquear operaciones protegidas cuando la revisión se aplique. |

## 4. Activación y sesión Relay del Sincronizador

La instalación se crea desde `plataform.rutx.quest`. El técnico usa un código de activación de un solo uso; a cambio, el Sincronizador recibe una credencial exclusiva de esa PC.

```json
POST /installations/activate
{
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "activationCode": "ACT-UN-SOLO-USO",
  "machineFingerprint": "sha256-huella-local-controlada",
  "synchronizerVersion": "2.4.0"
}
```

```json
201 Created
{
  "clientId": "cli_coyatoc_000001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "installationCredential": "secreto-para-almacen-seguro-de-la-pc",
  "relayUrl": "wss://rutx.quest/api/v1/relay/connect",
  "licenseEnvelope": { "payload": "…", "signature": "…" }
}
```

La PC abre una conexión saliente persistente; no recibe conexiones públicas y no requiere una IP o subdominio propio.

```text
GET wss://rutx.quest/api/v1/relay/connect
Authorization: Bearer <installationCredential>
X-RutX-Sync-Instance: syn_coyatoc_matriz_01
```

RutX valida la credencial, desafía con un `nonce`, asocia la sesión autenticada a `syncInstanceId` y mantiene su estado de presencia `online` u `offline`.

## 5. QR y enrolamiento del teléfono

El Sincronizador genera el QR sólo con una licencia válida. Antes de mostrarlo, registra en el Hub el **hash** de un `enrollmentToken` aleatorio, junto con el `syncInstanceId`, la expiración y el estado `unused`. El QR no lleva IP, URL por cliente, secreto de instalación, contraseña ni firma privada.

```json
{
  "schemaVersion": 1,
  "purpose": "mobile-enrollment",
  "clientId": "cli_coyatoc_000001",
  "licenseId": "LICX-434F-5941-0001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "enrollmentToken": "token-aleatorio-base64url",
  "issuedAt": "2026-08-16T19:00:00Z",
  "expiresAt": "2026-08-16T19:15:00Z",
  "qrNonce": "nonce-unico"
}
```

El token no necesita la firma privada de RutX: su aleatoriedad, duración breve, uso único y el registro previo en el Hub autenticado lo convierten en una invitación segura. La app reclama el QR por la misma API central:

```json
POST /mobile/enroll/claim
{
  "enrollmentToken": "token-aleatorio-base64url",
  "qrNonce": "nonce-unico",
  "devicePublicKey": "base64url-clave-publica-generada-en-el-dispositivo",
  "deviceInfo": {
    "platform": "android",
    "appVersion": "3.2.0",
    "model": "SM-A556E",
    "osVersion": "14"
  }
}
```

El Hub reenvía el reclamo sólo a `syn_coyatoc_matriz_01`. El Sincronizador verifica licencia, token, vencimiento y cupo en `Extras.fdb`; al aceptar responde:

```json
201 Created
{
  "deviceId": "dev_01J8C8GQ0EXAMPLE",
  "deviceNumber": 1,
  "clientId": "cli_coyatoc_000001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "deviceStatus": "active",
  "accessToken": "jwt-corto-de-dispositivo",
  "refreshToken": "token-rotativo-de-dispositivo",
  "license": {
    "licenseId": "LICX-434F-5941-0001",
    "revision": 1,
    "permissions": ["sales"]
  }
}
```

## 6. Login de usuario móvil

La licencia no se envía como una contraseña. El dispositivo ya vinculado se autentica en cabecera; el usuario se autentica en el cuerpo.

```json
POST /mobile/auth/login
Authorization: Bearer <accessToken-de-dispositivo>
{
  "username": "vendedor01",
  "password": "secreto-del-usuario",
  "deviceId": "dev_01J8C8GQ0EXAMPLE"
}
```

```json
200 OK
{
  "sessionToken": "jwt-corto-de-usuario-y-dispositivo",
  "refreshToken": "token-rotativo",
  "user": {
    "userId": "microsip:ventas:1032",
    "displayName": "Vendedor Uno",
    "roles": ["sales"]
  },
  "context": {
    "clientId": "cli_coyatoc_000001",
    "syncInstanceId": "syn_coyatoc_matriz_01",
    "deviceId": "dev_01J8C8GQ0EXAMPLE"
  }
}
```

## 7. Venta, Relay y acuse final

La app guarda la venta en su cola local antes de intentar red. Al recuperar datos móviles, llama al mismo dominio único:

```json
POST /mobile/sales
Authorization: Bearer <sessionToken>
Idempotency-Key: 2ee2af88-f8a2-48ff-898c-5aa20c6cf77a
{
  "operationId": "op_01J8D1EXAMPLE",
  "occurredAt": "2026-08-16T19:22:11Z",
  "sale": {
    "localFolio": "MOV-000000128",
    "customerId": "microsip:cliente:849",
    "items": [
      { "productId": "microsip:articulo:220", "quantity": 2, "unitPrice": 43.5 }
    ],
    "payments": [
      { "method": "cash", "amount": 87.0 }
    ],
    "total": 87.0,
    "currency": "MXN"
  }
}
```

El Hub deriva el destino desde los claims de la sesión, envía el comando Relay a la PC conectada y espera su respuesta. El Sincronizador verifica nuevamente usuario, dispositivo, licencia, permisos e idempotencia antes de aplicar la venta en Microsip.

```json
200 OK
{
  "operationId": "op_01J8D1EXAMPLE",
  "status": "ACKNOWLEDGED",
  "synchronizerReceipt": "MSP-VENTA-58192",
  "processedAt": "2026-08-16T19:22:15Z"
}
```

La app elimina la venta de su cola sólo con `ACKNOWLEDGED` o `DUPLICATE` comprobable.

| Respuesta | Significado | Acción móvil |
|---|---|---|
| `ACKNOWLEDGED` | Venta registrada por Sincronizador. | Eliminar de cola y conservar acuse. |
| `DUPLICATE` | La misma clave ya fue registrada. | Eliminar de cola y guardar acuse existente. |
| `SYNC_OFFLINE` | La PC no tiene conexión Relay. | Mantener en cola; reintentar. |
| `NETWORK_UNAVAILABLE` | No hay conectividad hacia RutX. | Mantener en cola; reintentar. |
| `REJECTED` | Regla o licencia rechazó la venta. | Mostrar motivo; no reintentar automáticamente. |

## 8. Chequeo de licencia y auditoría

El chequeo nocturno sigue siendo saliente desde la PC:

```json
POST /installations/license/check
{
  "requestId": "req_01J9RUTXCOYA0001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "installedLicenseRevision": 1,
  "checkedAt": "2027-08-15T08:30:00Z",
  "synchronizerVersion": "2.4.0"
}
```

Cada evento registra UTC, `clientId`, `syncInstanceId`, `deviceId` cuando aplique, `operationId`, actor, resultado y código de error. No se almacenan contraseñas, secretos de instalación, tokens completos ni datos sensibles de pago en los logs.

## 9. Errores mínimos

| Código | Causa |
|---|---|
| `LICENSE_SIGNATURE_INVALID` | Documento alterado o firma inválida. |
| `LICENSE_BLOCKED` | Suspensión, revocación o vencimiento sin gracia. |
| `ENROLLMENT_TOKEN_INVALID` | QR vencido, consumido o no coincidente. |
| `DEVICE_LIMIT_REACHED` | No hay cupo de licencia. |
| `DEVICE_DISABLED` | Teléfono dado de baja. |
| `AUTH_INVALID` | Token o credenciales de usuario inválidas. |
| `SYNC_OFFLINE` | Instalación no conectada al Relay. |
| `VALIDATION_ERROR` | Operación mal formada o rechazada por negocio. |
