# Guía de implementación — App móvil RutX con Hub/Relay

**Repositorio objetivo:** `RutX-AppMovil`  
**Rama propuesta:** `feature/rutx-hub-relay-mobile`  
**Contrato fuente:** [Contrato técnico Hub/Relay](license-contract-mvp.md).

## Objetivo

Una sola APK sirve a todos los clientes. La única URL integrada en la app es:

```text
https://rutx.quest/api/v1
```

La app no configura IP, puerto, hostname de cliente ni endpoint de Sincronizador. Un QR sólo entrega identidad de vínculo y token de canje. El Hub identifica el Sincronizador destinatario por las credenciales emitidas después de ese canje.

## Cambios requeridos

| Área | Cambio | Criterio |
|---|---|---|
| Configuración API | Crear una única `rutxApiBaseUrl`; eliminar hosts por cliente. | Una compilación para todo RutX. |
| Arranque | Estados `notEnrolled`, `enrolled`, `authenticated`, `offlineQueueing`. | No operar ventas sin vínculo válido. |
| QR | Nuevo lector y pantalla de confirmación de cliente/instalación. | No editar destino manualmente. |
| Identidad de dispositivo | Generar par de claves local y enviar clave pública al canjear QR. | No usar IMEI, teléfono o identificador restringido. |
| Tokens | Guardar tokens y clave privada mediante almacenamiento seguro del SO. | Nunca en preferencias, logs o backups. |
| Login | Primero enrolar dispositivo; después autenticar usuario. | `licenseId` no viaja como contraseña. |
| Cola de ventas | Persistir operación antes de intentar red. | Sin pérdida al quedarse sin señal o cerrar la app. |
| Acuses | Aplicar `ACKNOWLEDGED`, `DUPLICATE`, `SYNC_OFFLINE`, `REJECTED`. | Nunca duplicar venta en reintento. |

## Flujo de enrolamiento

1. Pantalla **Configurar dispositivo** escanea QR.
2. Mostrar, sin permitir editar: `licenseId`, cliente e instalación. Pedir confirmación visual del técnico/usuario.
3. Generar o recuperar `devicePublicKey` desde almacenamiento seguro.
4. Llamar `POST /mobile/enroll/claim` contra `rutx.quest/api/v1`.
5. Si el Hub y el Sincronizador aceptan cupo y token, guardar `clientId`, `syncInstanceId`, `deviceId`, `deviceNumber`, tokens y resumen de licencia.
6. Cambiar a `enrolled`; habilitar login de usuario.

### Persistencia local mínima

```text
DeviceEnrollment(clientId, licenseId, syncInstanceId, deviceId,
                 deviceNumber, deviceKeyReference, enrollmentState, enrolledAtUtc)
UserSession(userId, roles, accessTokenReference, refreshTokenReference, expiresAtUtc)
OutboxOperation(operationId, idempotencyKey, type, bodyJson,
                state, retryCount, lastErrorCode, createdAtUtc, acknowledgedAtUtc)
```

El modelo de dominio y el repositorio local pueden ajustarse a la estructura Flutter existente; la separación entre enrolamiento, sesión y cola debe preservarse.

## Login de usuario

```http
POST https://rutx.quest/api/v1/mobile/auth/login
Authorization: Bearer <token-de-dispositivo>
Content-Type: application/json
```

```json
{
  "username": "vendedor01",
  "password": "secreto-del-usuario",
  "deviceId": "dev_01J8C8GQ0EXAMPLE"
}
```

La sesión resultante queda ligada a los claims `clientId`, `syncInstanceId`, `deviceId`, `userId` y roles. La pantalla no debe mostrar ni pedir LicenseKey para iniciar sesión.

## Cola durable de ventas

1. Al confirmar una venta, generar `operationId` e `idempotencyKey` UUID.
2. Guardar cuerpo completo y estado `pending` antes de cualquier `await` de red.
3. Enviar `POST /mobile/sales` con `Authorization` y cabecera `Idempotency-Key`.
4. Con `ACKNOWLEDGED` o `DUPLICATE`, guardar acuse y marcar la operación terminada.
5. Con timeout, falta de red, 5xx o `SYNC_OFFLINE`, conservar exactamente la misma operación y reintentar con espera incremental y *jitter*.
6. Con `REJECTED` o `VALIDATION_ERROR`, guardar motivo y solicitar corrección; no reintentar automáticamente.

> Una venta no se elimina porque la petición se envió: se elimina sólo cuando existe acuse del Sincronizador, directo a través del Hub.

## Estados UX mínimos

| Estado | Mensaje | Acción |
|---|---|---|
| `notEnrolled` | “Escanea el QR del Sincronizador.” | Enrolar. |
| `enrolled` | “Dispositivo vinculado; inicia sesión.” | Login. |
| `offlineQueueing` | “Sin señal; la venta se guardó para envío.” | Seguir vendiendo. |
| `syncOffline` | “RutX recibió la solicitud; el Sincronizador está temporalmente desconectado.” | No repetir la venta. |
| `rejected` | “La operación fue rechazada: {motivo}.” | Corregir con soporte. |
| `licenseBlocked` | “La licencia no permite nuevas operaciones.” | Contactar administrador. |

## Pruebas obligatorias

- QR vencido, consumido y perteneciente a otra instalación.
- Dos dispositivos reclamando el último cupo simultáneamente.
- Venta creada sin datos, reinicio de app y reintento posterior.
- Timeout después de aplicar venta: reintento devuelve acuse original, sin segunda venta.
- Teléfono deshabilitado: token renovado y venta deben ser rechazados.
- Sesión de usuario de cliente distinto o instalación distinta: rechazo.

## Ramas posteriores

Después de terminar y probar la rama principal, crear `feature/rutx-master-device-controls` para **Más → Configuración master**, reemplazo de teléfono y baja controlada.

