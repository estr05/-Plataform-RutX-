# Modelo local RutX en `Extras.fdb`

**Ubicación estándar:** `C:\Microsip Extras\Extras.fdb`  
**Responsable técnico:** Sincronizador RutX  
**Estado:** modelo de persistencia para el contrato de licenciamiento v1.

## 1. Frontera de datos

`Extras.fdb` es la base complementaria de RutX en cada instalación. Guarda lo que no pertenece a Microsip: licencia firmada, identidad de instalación, cupos de teléfonos y auditoría técnica. RutX puede leer los datos necesarios de la base central de Microsip para sincronizar, pero no crea tablas, columnas ni escrituras nuevas en ella.

| Base | Propiedad | Uso de RutX | Regla |
|---|---|---|---|
| Base central Microsip | Microsip | Consulta de información operativa existente. | Solo lectura para el alcance de licencias. |
| `C:\Microsip Extras\Extras.fdb` | RutX | Licencia local, dispositivos, preferencias y auditoría. | Lectura/escritura exclusiva del Sincronizador RutX. |

Las migraciones de esta base deben tener número de versión, ser idempotentes y ejecutarse desde el instalador/actualizador del Sincronizador. Esta base debe incluirse en el respaldo ordinario de la instalación.

## 2. Convenciones compartidas

| Concepto | Convención |
|---|---|
| Fechas | UTC en `TIMESTAMP`. |
| Identidad | Cada registro operativo usa `LICENSE_ID`, `CLIENT_ID` y `SYNC_INSTANCE_ID` cuando corresponda. |
| Estado de licencia | `ACTIVE`, `SUSPENDED`, `REVOKED`; gracia y vencimiento son estados calculados, no escritos en el documento firmado. |
| Estado de puesto | `AVAILABLE`, `PENDING`, `ACTIVE`, `DISABLED`. |
| Secretos | No se guardan tokens QR ni claves de instalación en texto plano; solo hashes. |
| Revisión | Jamás se aplica una `LICENSE_REVISION` menor a la vigente. |

## 3. `RUTX_INSTALLATION`

Almacena la identidad local con la que el Sincronizador se presenta ante RutX central.

| Campo | Tipo Firebird sugerido | Uso |
|---|---|---|
| `ID` | `BIGINT` identidad | Clave interna. |
| `CLIENT_ID` | `VARCHAR(64)` | Cliente RutX. |
| `SYNC_INSTANCE_ID` | `VARCHAR(96)` | Identidad de esta PC/instalación. |
| `SYNC_ENDPOINT` | `VARCHAR(500)` | Hostname público de este Sincronizador bajo `rutx.quest` (vía túnel). |
| `INSTALLATION_KEY_PROTECTED` | `BLOB SUB_TYPE TEXT` | Secreto local protegido por el sistema operativo. |
| `INSTALLATION_KEY_HASH` | `CHAR(64)` | SHA-256, útil para diagnóstico y registro central. |
| `CREATED_AT` / `UPDATED_AT` | `TIMESTAMP` | Auditoría. |

Debe existir un único registro de instalación activa. La clave de instalación no se entrega a teléfonos ni se incluye en códigos QR.

## 4. `RUTX_LICENSE_CURRENT`

Conserva la última revisión firmada aplicada y permite arrancar sin Internet.

| Campo | Tipo Firebird sugerido | Uso |
|---|---|---|
| `ID` | `BIGINT` identidad | Clave interna. |
| `LICENSE_ID` | `VARCHAR(64)` | LicenseKey comercial. |
| `CLIENT_ID` | `VARCHAR(64)` | Cliente inmutable. |
| `SYNC_INSTANCE_ID` | `VARCHAR(96)` | Instalación destinataria. |
| `LICENSE_REVISION` | `INTEGER` | Revisión firmada aplicada. |
| `STATUS` | `VARCHAR(20)` | `ACTIVE`, `SUSPENDED` o `REVOKED`. |
| `SIGNED_DOCUMENT` | `BLOB SUB_TYPE TEXT` | Sobre firmado completo: payload, hash, firma y key id. |
| `PAYLOAD_HASH` | `CHAR(64)` | SHA-256 validado. |
| `EXPIRES_AT` | `TIMESTAMP` | Expiración UTC del payload. |
| `GRACE_DAYS` | `SMALLINT` | Cinco en el MVP. |
| `LAST_CHECK_AT` | `TIMESTAMP` nullable | Última comprobación central exitosa. |
| `LAST_ACCEPTED_UTC` | `TIMESTAMP` nullable | Detección de retroceso de reloj. |
| `CREATED_AT` / `UPDATED_AT` | `TIMESTAMP` | Auditoría. |

Se mantiene una sola fila por `SYNC_INSTANCE_ID`. La sustitución de esa fila se realiza dentro de una transacción después de validar hash, firma, identidad y monotonía de revisión.

## 5. `RUTX_LICENSE_AUDIT`

Registra las comprobaciones nocturnas y los intentos de actualización sin almacenar secretos.

| Campo | Tipo Firebird sugerido | Uso |
|---|---|---|
| `ID` | `BIGINT` identidad | Clave interna. |
| `LICENSE_ID` | `VARCHAR(64)` nullable | Licencia relacionada. |
| `REQUEST_ID` | `VARCHAR(96)` nullable | Identificador de un ciclo nocturno. |
| `EVENT_TYPE` | `VARCHAR(48)` | `CHECK_OK`, `UPDATE_APPLIED`, `UNREACHABLE`, `SIGNATURE_REJECTED`, `IDENTITY_REJECTED` o `COMMAND_APPLIED`. |
| `DETAIL_JSON` | `BLOB SUB_TYPE TEXT` nullable | Resumen sin credenciales. |
| `CREATED_AT` | `TIMESTAMP` | Tiempo UTC. |

## 6. `DISP_VENTA`: cupos, no historial de teléfonos

Un registro representa un puesto operativo dentro de una licencia. Para una licencia de siete dispositivos, existen siete puestos con `NO_DISPOSITIVO` del 1 al 7. El teléfono que ocupa el puesto puede cambiar; la auditoría conserva su historia.

| Campo | Tipo Firebird sugerido | Uso |
|---|---|---|
| `ID` | `BIGINT` identidad | Clave interna. |
| `LICENSE_ID` | `VARCHAR(64)` | Aislamiento por licencia. |
| `CLIENT_ID` | `VARCHAR(64)` | Aislamiento por cliente. |
| `SYNC_INSTANCE_ID` | `VARCHAR(96)` | Aislamiento por instalación. |
| `NO_DISPOSITIVO` | `SMALLINT` | Número visible de puesto, por ejemplo `1` para `1/7`. |
| `DEVICE_ID` | `VARCHAR(128)` nullable | Identidad técnica del teléfono actual. |
| `DEVICE_NAME` | `VARCHAR(160)` nullable | Diagnóstico. |
| `PLATFORM` | `VARCHAR(24)` nullable | Por ejemplo, `android`. |
| `ESTADO` | `VARCHAR(24)` | `AVAILABLE`, `PENDING`, `ACTIVE` o `DISABLED`. |
| `ENROLLMENT_TOKEN_HASH` | `CHAR(64)` nullable | SHA-256 del token QR activo. |
| `RESERVED_UNTIL` | `TIMESTAMP` nullable | Caducidad de la reserva (30 minutos). |
| `ENROLLED_AT` | `TIMESTAMP` nullable | Momento de canje del QR. |
| `FIRST_SYNC_AT` | `TIMESTAMP` nullable | Confirmación de activación. |
| `LAST_SYNC_AT` | `TIMESTAMP` nullable | Última sincronización del equipo. |
| `LAST_LICENSE_REVISION` | `INTEGER` nullable | Última revisión reportada por el equipo. |
| `CREATED_AT` / `UPDATED_AT` | `TIMESTAMP` | Auditoría. |

Se declara única la combinación `LICENSE_ID + NO_DISPOSITIVO`. Para liberar un puesto no se elimina la fila: se limpia el `DEVICE_ID`, se cambia el estado a `AVAILABLE` o `DISABLED` según la causa y se registra el evento en auditoría.

## 7. Ciclo de cupo y regla de concurrencia

| Transición | Condición | Contador |
|---|---|---|
| `AVAILABLE → PENDING` | QR canjeado, token válido y existe cupo. | Reserva; no suma activo. |
| `PENDING → ACTIVE` | Primera sincronización válida del mismo `DEVICE_ID`. | Aumenta activos en uno. |
| `PENDING → AVAILABLE` | Vence `RESERVED_UNTIL` o falla el enrolamiento. | Libera el puesto. |
| `ACTIVE → AVAILABLE` | Sustitución local autorizada. | Reduce activos y permite reemplazo. |
| `ACTIVE → DISABLED` | Pérdida o deshabilitación ordenada. | Reduce activos y rechaza ese equipo. |

El canje del QR se realiza en una transacción Firebird. Antes de reservar, se cuentan los puestos `PENDING` y `ACTIVE`; si alcanzan `maxMobileDevices`, se responde `DEVICE_LIMIT_REACHED`. El panel puede mostrar `6 / 7 activos · 1 pendiente`, pero solo los `ACTIVE` se reportan como dispositivos activos.

## 8. `DISP_VENTA_AUDIT`

| Campo | Tipo Firebird sugerido | Uso |
|---|---|---|
| `ID` | `BIGINT` identidad | Clave interna. |
| `DISP_VENTA_ID` | `BIGINT` | Puesto involucrado. |
| `EVENT_TYPE` | `VARCHAR(48)` | `QR_CREATED`, `ENROLLMENT_RESERVED`, `DEVICE_ACTIVATED`, `RESERVATION_EXPIRED`, `DEVICE_RELEASED`, `DEVICE_DISABLED` o `LICENSE_REJECTED`. |
| `DEVICE_ID` | `VARCHAR(128)` nullable | Equipo involucrado. |
| `LICENSE_REVISION` | `INTEGER` nullable | Revisión asociada. |
| `ACTOR` | `VARCHAR(96)` nullable | App, panel local, técnico o comando RutX. |
| `DETAIL_JSON` | `BLOB SUB_TYPE TEXT` nullable | Contexto no sensible. |
| `CREATED_AT` | `TIMESTAMP` | Tiempo UTC. |

## 9. Extensiones futuras

Ubicaciones, direcciones, notificaciones y preferencias pueden vivir en esta base, pero se agregan solo cuando exista un contrato y una migración versionada propia. No se crean tablas especulativas durante el desarrollo de licencias.
