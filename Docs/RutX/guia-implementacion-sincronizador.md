# Guía de implementación — Sincronizador RutX con Hub/Relay

**Repositorio objetivo:** `RutX-Sincronizador`  
**Rama propuesta:** `feature/rutx-hub-relay-synchronizer`  
**Contrato fuente:** [Contrato técnico Hub/Relay](license-contract-mvp.md).

## Objetivo

El Sincronizador ya no expone una API pública que la app deba localizar. Es un cliente conectado de forma persistente al Hub central de RutX:

```text
App → https://rutx.quest/api/v1 → Relay autenticado → Sincronizador → Microsip / Extras.fdb
```

No hay IP pública, port-forwarding, Cloudflare Tunnel ni subdominio por cliente. El servicio local se comunica **hacia afuera** con RutX y recibe por esa misma sesión los comandos que le corresponden.

## Componentes .NET requeridos

| Componente | Responsabilidad |
|---|---|
| `InstallationActivationService` | Canjear código de una vez por credencial protegida e instalación asignada. |
| `RelayConnectionService` | Sesión saliente `wss`, heartbeat, reconexión, presencia y reanudación. |
| `RelayCommandDispatcher` | Procesar `enrollment.claim`, `user.login`, `sale.submit`, licencia y comandos de dispositivos. |
| `LicenseService` | Verificar Ed25519, aplicar revisión de licencia y evaluar gracia. |
| `EnrollmentService` | Generar QR, registrar hash de token en Hub y asignar cupos. |
| `DeviceRegistryRepository` | Gestionar `DISP_VENTA` y auditoría en `Extras.fdb`. |
| `OperationIdempotencyRepository` | Guardar operación/acuses para repetir sin duplicar. |
| `NightlyLicenseCheckService` | Consultar licencia y reportar estado de modo programado. |

## Activación inicial

1. Instalar servicio y crear/migrar `Extras.fdb` de forma idempotente.
2. El técnico selecciona **Activar instalación RutX** e ingresa el código de una vez creado desde Plataforma RutX.
3. Llamar `POST /installations/activate`.
4. Guardar credencial con DPAPI o equivalente. Nunca en `appsettings.json`, texto plano o logs.
5. Verificar el sobre de licencia Ed25519 y persistir en transacción.
6. Abrir `RelayConnectionService` y mostrar la instalación `online` en Plataforma RutX.

## Sesión Relay persistente

Conectar a `wss://rutx.quest/api/v1/relay/connect` desde un `IHostedService` de larga vida. Autenticar con credencial de instalación y declarar `syncInstanceId`.

| Evento | Acción |
|---|---|
| Conexión | Informar versión, estado de licencia, cupos y cursor de operaciones. |
| Heartbeat | Responder presencia mínima y actualizar `lastSeen`. |
| Corte técnico | Auditar y reconectar con backoff exponencial y *jitter*. |
| Credencial revocada | Detener reconexión y elevar alerta local. |
| Licencia nueva | Verificar firma, identidad y monotonicidad de revisión antes de guardar. |

El Hub responde `SYNC_OFFLINE` a la app mientras esta conexión no exista. El Sincronizador sigue conservando trabajo local y recuperación en `Extras.fdb`.

## QR de enrolamiento

1. Exigir licencia `active` y cupo libre.
2. Generar `enrollmentToken` aleatorio, `qrNonce` y expiración de 10–15 minutos.
3. Registrar por Relay en Hub el **hash** del token, `syncInstanceId`, expiración y estado `unused`.
4. Esperar acuse del Hub antes de pintar QR.
5. El QR contiene únicamente datos del contrato: sin IP, host local, secreto o contraseña.
6. Cuando llegue `enrollment.claim`, validar cupo/token en transacción Firebird, crear dispositivo y responder el contexto seguro.

## Procesamiento de operaciones

Para `sale.submit`:

1. Validar que los claims recibidos pertenecen a esta instalación: dispositivo activo, usuario, permiso y licencia.
2. Consultar `idempotencyKey`. Si existe, responder con el acuse persistido.
3. Registrar intento antes de tocar Microsip.
4. Aplicar la venta por el flujo existente de Microsip.
5. Guardar acuse duradero y responder `ACKNOWLEDGED`; si la regla rechaza, responder `REJECTED` con código seguro.

Nunca responder éxito antes de tener confirmación de Microsip. Nunca crear otro folio para un reintento con igual clave.

## Datos locales mínimos en Extras.fdb

| Tabla | Uso nuevo o requerido |
|---|---|
| `RUTX_LICENSE_CURRENT` | Revisión firmada, vigencia, estado y gracia. |
| `DISP_VENTA` | `device_id`, número, clave pública, estado y fechas. |
| `DISP_VENTA_AUDIT` | QR, alta, baja, reemplazo y actor. |
| `RUTX_ENROLLMENT_TOKEN` | Hash, vencimiento, consumo y estado. |
| `RUTX_RELAY_OPERATION` | `operation_id`, idempotencia, estado y acuse. |
| `RUTX_RELAY_STATE` | Cursor, último heartbeat, versión y última conexión. |

No modificar las tablas centrales de Microsip para soportar licencias, cupos o Relay.

## Pruebas obligatorias

- Reconexión posterior a corte de Internet y reinicio del servicio.
- QR/canjes simultáneos con último cupo.
- Repetición de `sale.submit` con idéntica `idempotencyKey`.
- Licencia firmada con instalación, cliente o revisión inválidos.
- Reinicio entre aplicar Microsip y confirmar Relay: recuperar sin duplicar.
- Credencial revocada y estado offline.

## Ramas posteriores

- `feature/rutx-relay-observability`: presencia, latencia, logs correlacionados y métricas.
- `feature/rutx-relay-reconciliation`: reconciliación de operaciones pendientes.
- `feature/rutx-master-device-controls`: comandos firmados de baja/reemplazo.

