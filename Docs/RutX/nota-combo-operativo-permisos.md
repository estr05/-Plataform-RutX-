# Nota de producto y contrato — Combo operativo inicial y permisos RutX

**Estado:** decisión de producto acordada; pendiente de implementación en las ramas indicadas.  
**Alcance:** esta nota define licenciamiento, contratos y plan técnico. No modifica la aplicación Laravel, la app móvil ni el Sincronizador.

## 1. Decisión

El primer paquete comercial de RutX se llamará **Ventas**. En la licencia no se guardará un permiso ambiguo como `ventas_completo`; se conservarán **permisos atómicos**. El paquete comercial será una agrupación visible y auditable de esos permisos.

> **La licencia autoriza con permisos atómicos. El combo solo facilita la venta, la selección en Plataforma y la lectura humana.**

Esta separación permite comenzar con un solo paquete completo y, después, vender, suspender o habilitar capacidades individuales sin cambiar la `licenseId`, la topología Hub/Relay ni la aplicación instalada.

## 2. Catálogo inicial de permisos

| Permiso | Nombre visible | Autoriza | No autoriza por sí mismo |
|---|---|---|---|
| `sales` | Venta de contado | Registrar ventas de contado y obtener su acuse. | Venta a crédito, cobro, merma o mapas. |
| `credit_sales` | Venta a crédito | Registrar una venta cuya forma comercial sea crédito. | Cobrar saldos ni modificar condiciones de crédito fuera de las reglas Microsip. |
| `no_sale` | No venta | Registrar una visita/intento sin venta, con motivo permitido. | Crear una venta por importe cero para simular el evento. |
| `collections` | Cobro | Registrar cobros y recibir acuse del Sincronizador. | Crear una venta a crédito ni alterar saldos manualmente. |
| `map.customers` | Mapa de clientes | Consultar la ubicación y datos permitidos de clientes. | Consultar ubicación de vendedores. |
| `map.sellers` | Mapa de vendedores | Consultar ubicación y estado permitido de vendedores. | Consultar clientes fuera del alcance del usuario. |
| `shrinkage` | Merma | Registrar y consultar mermas conforme a reglas de inventario. | Ajustar inventario directamente sin validación Microsip. |

Los nombres técnicos son estables y se escriben siempre en minúsculas. El punto en `map.customers` y `map.sellers` representa un **namespace**; no es una URL ni modifica el dominio único `https://rutx.quest/api/v1`.

## 3. Combo comercial inicial

| Código de combo | Nombre comercial | Permisos incluidos en la revisión inicial |
|---|---|---|
| `ventas` | Ventas | `sales`, `credit_sales`, `no_sale`, `collections`, `map.customers`, `map.sellers`, `shrinkage` |

Para el primer cliente, incluido Coyatoc, el combo `ventas` habilita los siete permisos. No se crearán claves de licencia diferentes por cada combinación. La misma `licenseId` conserva su identidad comercial y RutX emite una **nueva revisión firmada** cuando cambie el conjunto de permisos, cupos o vigencia.

En el futuro se podrán crear combos como `ventas-basico`, `cobranza` o `geolocalizacion`; siempre deberán expandirse a una lista explícita de permisos antes de firmar una revisión. La aplicación, el Hub y el Sincronizador **nunca toman una decisión de autorización basándose únicamente en el nombre del combo**.

## 4. Representación en la licencia firmada

El origen de verdad de derechos es `permissions`. `commercialPackage` se conserva para operación comercial, presentación y auditoría; no sustituye la validación de cada permiso.

```json
{
  "schemaVersion": 1,
  "licenseId": "LICX-434F-5941-0001",
  "clientId": "cli_coyatoc_000001",
  "issuedAt": "2026-08-17T00:00:00Z",
  "startsAt": "2026-08-17T00:00:00Z",
  "expiresAt": "2027-08-16T23:59:59Z",
  "offlineGraceDays": 5,
  "maxMobileDevices": 7,
  "commercialPackage": {
    "code": "ventas",
    "displayName": "Ventas",
    "revision": 1
  },
  "permissions": [
    "sales",
    "credit_sales",
    "no_sale",
    "collections",
    "map.customers",
    "map.sellers",
    "shrinkage"
  ],
  "revision": 2,
  "status": "active",
  "keyId": "rutx-ed25519-2026-01"
}
```

La revisión se firma completa con Ed25519 y el Sincronizador verifica firma, `clientId`, vigencia, monotonía de `revision` y permisos antes de reemplazar el documento local de `Extras.fdb`. Una licencia revocada, suspendida o sin gracia no permite nuevas operaciones protegidas, aunque la app conserve el último resumen en caché.

## 5. Autorización por capa

La interfaz puede ocultar acciones no autorizadas para mejorar la experiencia, pero no es el control de seguridad. La decisión se repite donde cada operación puede ser aceptada o rechazada.

| Capa | Responsabilidad obligatoria |
|---|---|
| **Plataforma RutX** | Mantener el catálogo, seleccionar el combo, expandirlo a permisos, emitir revisión firmada y auditar quién cambió cada derecho. |
| **Hub `rutx.quest/api/v1`** | Asociar sesión a `clientId`, `syncInstanceId`, `deviceId`, `userId`, permisos y revisión; validar que la ruta solicitada requiere el permiso correcto; enrutar solo al Relay de la instalación contenida en los claims. |
| **Sincronizador** | Revalidar licencia local firmada, usuario, dispositivo activo, permiso, reglas Microsip e idempotencia antes de tocar datos locales. |
| **App móvil** | Mostrar únicamente capacidades concedidas y conservar en cola las operaciones offline; no asumir éxito ni omitir la validación posterior del Hub/Sincronizador. |

Un cambio de permisos se aplica al Sincronizador en su chequeo nocturno o cuando reciba la nueva revisión por Relay. La app obtiene el resumen actualizado en su siguiente login o sincronización; al llegar una revocación, una operación pendiente puede terminar en `REJECTED` y debe conservar el motivo, no reintentarse ciegamente.

## 6. Matriz de operaciones propuesta

Estas rutas son **objetivo de contrato** para el Hub. No están implementadas todavía y se mantienen bajo el dominio único; el Hub las reenvía por Relay en vez de exponer el puerto `5047`.

| Operación de negocio | Ruta pública objetivo | Permiso Hub/Sincronizador | Regla de cola e idempotencia |
|---|---|---|---|
| Venta de contado | `POST /mobile/sales` con `sale.saleType: "cash"` | `sales` | Persistir antes de enviar; misma `idempotencyKey` hasta `ACKNOWLEDGED` o `DUPLICATE`. |
| Venta a crédito | `POST /mobile/sales` con `sale.saleType: "credit"` | `credit_sales` | Misma garantía de idempotencia; el Sincronizador valida cliente y reglas de crédito. |
| No venta | `POST /mobile/no-sales` | `no_sale` | Evento durable de visita; no crear folio de venta. |
| Cobro | `POST /mobile/collections` | `collections` | Persistir antes de enviar; validar importe, cuenta y recibo en Microsip. |
| Mapa de clientes | `GET /mobile/maps/customers` | `map.customers` | No crea operación contable; cache local con expiración y alcance de usuario. |
| Mapa de vendedores | `GET /mobile/maps/sellers` | `map.sellers` | No revelar ubicaciones fuera de las reglas del cliente y rol del usuario. |
| Merma | `POST /mobile/shrinkages` | `shrinkage` | Persistir, validar motivo/cantidades y obtener acuse antes de cerrar cola. |

Las ventas en efectivo y crédito comparten el recurso `sales` para no duplicar contratos, pero se autorizan con permisos diferentes mediante `sale.saleType`. El Hub y el Sincronizador deben rechazar cualquier combinación inconsistente, por ejemplo una venta `credit` con solo `sales`.

## 7. Plan completo de adopción

### Fase 0 — Contrato y catálogo

Primero se fija este catálogo en la documentación y en una fuente única de configuración del backend Laravel. Cada permiso debe tener código, nombre, descripción, estado y tipo de operación. El selector de licencia debe guardar el código de combo seleccionado para auditoría y expandir siempre a permisos atómicos antes de firmar.

**Criterio de cierre:** una revisión Ed25519 emitida para Coyatoc contiene exactamente los siete permisos y la verificación de firma reproduce el mismo documento en `Extras.fdb`.

### Fase 1 — Plataforma RutX y Hub

En la rama de Laravel se agregará el catálogo administrable, el selector inicial del combo `ventas`, historial de revisiones y auditoría de cambios. Se incorporará un `PermissionResolver` del lado servidor: recibe el combo, produce la lista canónica, la valida contra catálogo y bloquea permisos desconocidos. El Hub exigirá un permiso por cada ruta y añadirá `licenseRevision` al contexto de sesión.

**Rama propuesta:** `sprint/3-permission-catalog`.

**Criterio de cierre:** un administrador RutX puede emitir, renovar o modificar el combo de un cliente sin cambiar `licenseId`; la auditoría muestra actor, antes/después, revisión y razón comercial.

### Fase 2 — Sincronizador

En `RutX-Sincronizador`, el `LicenseService` deserializa y persiste la lista de permisos firmada. El `RelayCommandDispatcher` deja de tener únicamente `sale.submit` y agrega manejadores explícitos para venta, no venta, cobro, mapas y merma. Cada manejador usa un evaluador común, por ejemplo `PermissionEvaluator.Require("collections")`, antes de llegar a Microsip.

**Rama propuesta:** `feature/rutx-hub-relay-permissions` sobre `feature/rutx-hub-relay-synchronizer`.

**Criterio de cierre:** el Sincronizador rechaza por código seguro una operación cuyo permiso no esté en la licencia, incluso si la app consiguió enviar el request; las operaciones autorizadas preservan idempotencia y acuse durable.

### Fase 3 — App móvil

En `RutX-AppMovil`, la sesión y el resumen de licencia guardan `permissions` y `licenseRevision`. Un `FeatureGate` central decide navegación, botones y formularios, sin dispersar comparaciones de cadenas por pantallas. La cola offline incorpora tipos `sale`, `no_sale`, `collection` y `shrinkage`; mapas se cachean con expiración y nunca cambian el destino de red.

**Rama propuesta:** `feature/rutx-hub-relay-permissions` sobre `feature/rutx-hub-relay-mobile`.

**Criterio de cierre:** un usuario sin `collections` no ve ni ejecuta Cobro; si recibe una licencia actualizada, la app refresca el menú y el Sincronizador sigue siendo la barrera final.

### Fase 4 — Pruebas integradas y liberación por cliente

Antes de activar una licencia de producción, se prueban los siete permisos y sus denegaciones desde Hub a Sincronizador. Deben cubrirse app sin red, Relay desconectado, reintento con la misma clave, revisión de licencia reducida y equipo perdido.

**Criterio de cierre:** una matriz de pruebas por permiso muestra tanto el camino permitido como el denegado, con `operationId`, `idempotencyKey`, `deviceId` y acuse auditables de extremo a extremo.

## 8. Reglas que no se abren de nuevo

| Regla | Decisión conservada |
|---|---|
| Dominio móvil | Una sola API: `https://rutx.quest/api/v1`. |
| Enrutamiento | Claims del dispositivo/sesión: `clientId`, `syncInstanceId`, `deviceId`; nunca URL o IP elegida por la app. |
| Licencia | `licenseId` estable, revisión firmada y 5 días de gracia. |
| Fuente contable | Microsip sigue siendo responsabilidad del Sincronizador; el Hub es identidad y relay. |
| Autorización | Permiso atómico validado en Hub y Sincronizador; la UI no es barrera de seguridad. |
| Dispositivos | QR de un solo uso, cupos locales en `Extras.fdb` y baja/reemplazo auditables. |

## 9. Decisiones pendientes, no bloqueantes

La definición funcional de cada formulario todavía requiere refinamiento con reglas Microsip: catálogo de motivos de no venta y merma, tipos de cobro, documentos aplicables, qué datos de ubicación son visibles por rol y la política de retención de geolocalización. Esas reglas deben convertirse en validaciones del Sincronizador y contratos JSON antes de conectar pantallas reales.

