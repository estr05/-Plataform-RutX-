# Configuración master y ciclo de vida de dispositivos RutX

**Estado:** incremento posterior al núcleo de licenciamiento.  
**Precondición:** la licencia firmada, consulta nocturna, descarga matutina y enrolamiento QR ya funcionan de forma estable.

## 1. Objetivo

La sección **Más → Configuración master** permite consultar la configuración de licencia del teléfono y, cuando un administrador local de Microsip se acredita, liberar un puesto para reemplazar un teléfono. El control central de un equipo perdido se realiza desde RutX y se aplica localmente cuando el Sincronizador recibe la orden firmada.

| Capa | Responsable | Acción permitida |
|---|---|---|
| App móvil | Usuario y administrador Microsip acreditado. | Consultar y solicitar liberación local. |
| Sincronizador | Regla local de autoridad. | Validar permisos, cambiar puestos y auditar. |
| RutX interno | Personal RutX. | Emitir orden firmada para deshabilitar un equipo perdido. |

## 2. Información visible en la app

La pantalla es informativa. Muestra datos operativos, no secretos.

```json
{
  "contractVersion": 1,
  "device": {
    "deviceNumber": 1,
    "deviceLabel": "Dispositivo 001 de 007",
    "status": "ACTIVE",
    "deviceName": "Samsung SM-A155M",
    "lastSyncAt": "2026-08-16T13:20:00Z"
  },
  "license": {
    "licenseId": "LICX-434F-5941-0001",
    "clientId": "clt_000001",
    "syncInstanceId": "syn_coyatoc_matriz_01",
    "revision": 2,
    "expiresAt": "2027-08-15T23:59:59Z",
    "offlineGraceDays": 5,
    "permissions": ["sales"]
  },
  "synchronizer": {
    "displayName": "Coyatoc — Matriz",
    "reachable": true
  }
}
```

No se muestran `enrollmentToken`, clave de instalación, firma privada, documento crudo ni credenciales de Microsip.

## 3. Sesión de administración local

Para actuar sobre dispositivos, la app solicita reautenticación a su Sincronizador. La contraseña se transmite una sola vez por el canal seguro y no se persiste. El Sincronizador verifica el privilegio contra Microsip o su regla local y devuelve un `masterSessionToken` de diez minutos, ligado a `deviceId`, `licenseId` y `syncInstanceId`.

| Regla | Decisión |
|---|---|
| Autorización | La toma el Sincronizador; la app nunca decide roles. |
| Contraseña | No se almacena en la app. |
| Token | Solo memoria, alcance `device_management`, expira en diez minutos. |
| Auditoría | Cada intento y acción queda en `DISP_VENTA_AUDIT`. |

## 4. Sustitución local de un teléfono disponible

Si el teléfono anterior puede ser administrado por la empresa, un administrador solicita liberar el puesto. El Sincronizador cambia el puesto `ACTIVE → AVAILABLE`, conserva el historial en auditoría y permite generar un QR para que el nuevo teléfono reciba el mismo número operativo tras la primera sincronización.

```json
{
  "action": "release_for_replacement",
  "licenseId": "LICX-434F-5941-0001",
  "clientId": "clt_000001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "targetDeviceNumber": 1,
  "reason": "replacement",
  "masterSessionToken": "short_lived_token"
}
```

La acción no elimina ventas, cobros, cierres ni registros de auditoría. Solo desconecta la identidad técnica del teléfono de su cupo de licencia.

## 5. Teléfono perdido: orden desde RutX

Cuando el teléfono se pierde, el personal RutX puede emitir una orden firmada para deshabilitar un puesto. El Sincronizador recibe la orden durante la comprobación nocturna o al pulsar **Comprobar licencia ahora**, valida firma e identidad, cambia el puesto `ACTIVE → DISABLED` y registra su aplicación.

```json
{
  "commandType": "disable_lost_device",
  "commandId": "cmd_01J8Q4KSMH",
  "licenseId": "LICX-434F-5941-0001",
  "clientId": "clt_000001",
  "syncInstanceId": "syn_coyatoc_matriz_01",
  "targetDeviceNumber": 1,
  "targetDeviceId": "dev_7f9dc972a4e145bb",
  "reason": "lost_device",
  "issuedAt": "2026-08-16T02:10:00Z",
  "signature": "BASE64URL_ED25519_SIGNATURE"
}
```

Hasta que el Sincronizador reciba esa orden, RutX central no puede bloquear un sitio totalmente desconectado. Esta limitación es inherente a la operación offline y debe comunicarse al equipo de soporte.

## 6. Rutas del incremento posterior

| Ruta | Método | Finalidad |
|---|---|---|
| `/api/v1/mobile/master-config` | `GET` | Datos informativos de la pantalla master. |
| `/api/v1/mobile/master-session` | `POST` | Reautenticación y token corto. |
| `/api/v1/mobile/devices/{number}/release` | `POST` | Liberación local autorizada. |
| `/api/v1/installations/license/check` | `POST` | Entrega de revisión y órdenes pendientes desde RutX. |
| `/api/v1/license/command-ack` | `POST` | Confirmación opcional de aplicación de una orden. |

Este módulo no cambia el cupo autorizado de la licencia. Si se requiere aumentar o disminuir el número de puestos, RutX emite una nueva revisión firmada con el nuevo `maxMobileDevices`.
