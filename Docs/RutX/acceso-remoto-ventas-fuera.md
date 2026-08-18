# Ventas en ruta y sin red — RutX Hub/Relay

## Principio

El vendedor puede estar en Tuxtla, Monterrey o sin señal en carretera. La aplicación móvil siempre usa el mismo API:

```text
https://rutx.quest/api/v1
```

No hay host de Coyatoc, IP local, puerto visible ni configuración de módem en el teléfono. El Hub central entrega la operación al Sincronizador correcto a partir de los *claims* del dispositivo enrolado.

## Dos colas, una venta

| Situación | Qué hace la app | Qué hace RutX | Resultado |
|---|---|---|---|
| App y Sincronizador conectados | Envía venta al Hub. | Relay la entrega y recibe acuse. | `ACKNOWLEDGED`. |
| App con Internet, Sincronizador desconectado | Envía una vez y conserva operación. | Persiste entrega pendiente o responde `SYNC_OFFLINE`. | No hay duplicado. |
| App sin Internet | Persiste venta en su Outbox. | No participa todavía. | Venta segura en el teléfono. |
| Red recuperada | Reintenta con misma `idempotencyKey`. | Reconcilia con el Sincronizador. | Un solo folio/acuse. |

> “Guardado local” es protección contra pérdida de señal; no es otro destino de red. El destino remoto siempre es el Hub central compartido.

## Flujo de venta

```text
1. App crea operationId + idempotencyKey y la persiste localmente.
2. POST /mobile/sales hacia rutx.quest/api/v1.
3. Hub valida token de dispositivo, usuario, licencia y cliente.
4. Hub busca el Relay activo de syncInstanceId.
5. Sincronizador procesa Microsip/Extras.fdb y devuelve acuse.
6. Hub devuelve el acuse a la app; la app cierra su operación local.
```

La entrega debe ser de tipo **al menos una vez**, protegida por idempotencia de extremo a extremo. La aplicación no puede asumir que un timeout equivale a falla: puede haber aplicado la venta y perdido la respuesta.

## Qué no se requiere

- No se abre el puerto `5047` en el router del cliente.
- No se compra ni se crea un subdominio por cliente o sucursal.
- No se agrega URL por cliente en `API Constant`.
- No se reconfigura un teléfono después de cambiar de red móvil o Wi‑Fi.

## Qué sí requiere el Hub

Cada Sincronizador debe conservar una sesión Relay saliente autenticada. Cuando no haya sesión, el Hub debe conservar evidencia de estado y la app debe mantener su Outbox; al reanudarse la sesión, las operaciones pendientes se concilian bajo `operationId` e `idempotencyKey`.

