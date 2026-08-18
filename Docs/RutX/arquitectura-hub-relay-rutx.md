# Arquitectura Hub/Relay central — RutX

## 1. Problema que resuelve

RutX tendrá muchos clientes, cada uno con una o varias PCs que ejecutan el Sincronizador. Los vendedores pueden estar en tienda, en ruta o en cualquier ciudad. Configurar una IP o una URL distinta para cada teléfono es costoso, frágil y difícil de soportar.

La arquitectura elegida elimina esa configuración: todas las apps usan `https://rutx.quest/api/v1`. La identidad del teléfono, obtenida al escanear un QR, permite a RutX determinar de forma segura qué instalación debe procesar cada operación.

## 2. Componentes y responsabilidades

| Componente | Propietario | Responsabilidad principal | No debe hacer |
|---|---|---|---|
| **Plataforma RutX** | RutX | Crear clientes, licencias, instalaciones, códigos QR, credenciales y auditoría. | Procesar contabilidad de Microsip. |
| **Hub/Relay RutX** | RutX | Autenticar apps, mantener el mapa de conexiones de Sincronizador y reenviar operaciones. | Decidir precios, existencias o reglas Microsip. |
| **Sincronizador** | Cliente, administrado por RutX | Validar licencia, controlar cupos, aplicar ventas y consultar Microsip/Extras.fdb. | Exponer su PC directamente a Internet. |
| **App móvil** | Cliente | Vender offline, escanear QR, mantener identidad de dispositivo y enviar/reintentar operaciones. | Conocer secretos de instalación o elegir arbitrariamente un Sincronizador. |

## 3. Topología única

```text
                         ┌─────────────────────────────────────┐
                         │          rutx.quest/api/v1          │
                         │  API móvil + Hub/Relay + licencias   │
                         └───────────────┬─────────────────────┘
                                         │
              WebSocket seguro saliente │  mapa: syncInstanceId → sesión
                                         │
     ┌───────────────────────────────────┼───────────────────────────────────┐
     │                                   │                                   │
┌────▼─────────────┐              ┌──────▼────────────┐              ┌───────▼───────────┐
│ Sync Coyatoc     │              │ Sync Cliente B    │              │ Sync Cliente C    │
│ syncInstanceId A │              │ syncInstanceId B  │              │ syncInstanceId C  │
│ PC + Extras.fdb  │              │ PC + Extras.fdb   │              │ PC + Extras.fdb   │
└──────────────────┘              └───────────────────┘              └───────────────────┘
       ▲
       │ comando relay y acuse
       │
┌──────┴─────────────────────────────────────────────────────────────┐
│ Apps móviles: siempre https://rutx.quest/api/v1                    │
└────────────────────────────────────────────────────────────────────┘
```

El Sincronizador abre y mantiene una sesión saliente autenticada —por ejemplo, WebSocket seguro `wss`— hacia el Hub. Como la conexión la inicia la PC, no se requiere IP pública, *port-forwarding*, DMZ ni un subdominio individual para cada instalación.

## 4. Identidades y por qué no se cruzan clientes

| Campo | Alcance | Generado por | Regla de seguridad |
|---|---|---|---|
| `clientId` | Empresa cliente | Plataforma RutX | Nunca se toma como autoridad desde una venta; se deriva del dispositivo autenticado. |
| `licenseId` | Contrato comercial estable | Plataforma RutX | Tiene índice único y revisión firmada. |
| `syncInstanceId` | Una PC/Sincronizador | Plataforma RutX | Único globalmente; conecta una operación al destino técnico. |
| `deviceId` | Teléfono enrolado | Sincronizador | Único; ocupa un cupo y está ligado a una instalación. |
| `userId` | Operador humano | Sistema de usuarios del cliente | Solo puede operar dentro de la instalación y permisos vinculados. |
| `installationKey` | Credencial de una PC | Plataforma RutX | Solo vive en la PC y en RutX; nunca aparece en QR o app. |
| `enrollmentToken` | Canje temporal de QR | Sincronizador/Hub | Un uso, vencimiento corto, hash persistido; no es token de sesión. |

La clave no es que la LicenseKey sea improbable de duplicar. La seguridad se deriva de una cadena completa: **QR firmado y temporal → `deviceId` activo → token de sesión con claims → enrutamiento interno por `syncInstanceId` → validación local del Sincronizador**.

## 5. Flujo de alta de una instalación

| Paso | Actor | Acción |
|---:|---|---|
| 1 | RutX | En `plataform.rutx.quest` crea `clientId`, licencia y `syncInstanceId`. |
| 2 | Plataforma | Emite una `installationKey` una sola vez y registra la instalación como `PENDING`. |
| 3 | Técnico | Instala el Sincronizador, guarda la credencial de instalación en el almacén seguro del sistema operativo y lo activa. |
| 4 | Sincronizador | Abre sesión saliente con el Hub y presenta `syncInstanceId` + prueba de posesión de su credencial. |
| 5 | Hub | Marca la instalación `ONLINE`, asocia la sesión al `syncInstanceId` y entrega la revisión de licencia vigente. |
| 6 | Sincronizador | Valida la firma Ed25519, guarda la revisión en `Extras.fdb` y puede generar QR. |

## 6. Flujo de enrolamiento de teléfono

```text
Plataforma RutX ── autoriza instalación ──► Sincronizador
Sincronizador ── QR temporal firmado ──► App móvil
App ── POST /mobile/enroll/claim ──► Hub RutX
Hub ── relay.enroll.claim ──► Sincronizador correcto
Sincronizador ── deviceId + estado PENDING ──► Hub ──► App
```

El QR contiene identidad, token temporal y metadatos verificables; **no contiene un endpoint por cliente**. La app llama al Hub común, y el Hub sólo acepta el canje si el QR, el `syncInstanceId`, la licencia y la sesión del Sincronizador coinciden.

## 7. Flujo de una venta desde cualquier lugar

1. La app registra la venta de forma durable en su cola local antes de intentar red.
2. Envía la operación a `POST /mobile/sales` en `rutx.quest/api/v1`, junto con su token de dispositivo y una clave de idempotencia.
3. El Hub obtiene `clientId`, `syncInstanceId` y `deviceId` desde el token; **no confía en un destino enviado por la app**.
4. El Hub localiza la sesión saliente del Sincronizador de esa instalación y transmite el comando.
5. El Sincronizador valida licencia, dispositivo, usuario e idempotencia; aplica la operación en su flujo de Microsip y responde con acuse.
6. El Hub devuelve el acuse a la app. Solo en ese momento la app elimina la venta de su cola local.

Si no hay datos móviles, si el Hub no está disponible o si el Sincronizador está desconectado, la app conserva la venta y reintenta según su política. El MVP no almacena ventas pendientes en RutX central: así evita que RutX se convierta accidentalmente en una segunda base contable.

## 8. Semántica de entrega

| Estado | Significado para la app | Acción de la app |
|---|---|---|
| `ACKNOWLEDGED` | El Sincronizador aceptó y registró la operación. | Eliminar de cola; guardar acuse. |
| `DUPLICATE` | Ya fue aceptada antes con la misma idempotencia. | Eliminar de cola; guardar acuse existente. |
| `REJECTED` | La operación fue rechazada por una regla de negocio o licencia. | Conservar evidencia y mostrar motivo; no reintentar ciegamente. |
| `SYNC_OFFLINE` | La instalación no tiene sesión relay. | Mantener cola y reintentar. |
| `NETWORK_UNAVAILABLE` | El teléfono no alcanzó RutX. | Mantener cola y reintentar cuando haya conectividad. |

## 9. Reglas de seguridad del Relay

| Riesgo | Control mínimo |
|---|---|
| Una app intenta elegir otro cliente | El Hub deriva el destino desde los claims del token de dispositivo, no desde JSON editable. |
| QR copiado | Token de un uso, expiración corta, hash de token, validación de `clientId`/`syncInstanceId`. |
| Venta repetida por reintento | `idempotencyKey` obligatoria y única por `deviceId`. |
| Sincronizador suplantado | `installationKey` no exportable, TLS, desafío con `nonce` y auditoría de conexión. |
| Instalación desconectada | No se confirma una venta; la app conserva su cola local. |
| Dispositivo perdido | Estado `DISABLED` bloquea sus tokens y el Hub rechaza sus llamadas. |

## 10. Decisión operativa

El Relay requiere un proceso de Hub que mantenga sesiones en memoria o en una capa compartida de presencia. No es un endpoint REST nocturno ordinario. Antes de implementar producción se debe elegir un hosting con proceso persistente y una estrategia de escalamiento para las sesiones de Sincronizador; la documentación no impone proveedor ni despliega infraestructura.

