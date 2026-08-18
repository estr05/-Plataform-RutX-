# Paquete técnico — RutX Hub/Relay y licenciamiento

**Estado:** arquitectura acordada para implementación.  
**Alcance:** este paquete es documentación; no realiza cambios a repositorios, infraestructura, bases de datos ni despliegues.

## Decisión rectora

Todas las aplicaciones móviles usan una única base de API:

```text
https://rutx.quest/api/v1
```

No existe una IP local, `lanEndpoint` ni hostname por cliente dentro de la app. El QR no modifica la URL de la app: entrega una identidad firmada para enlazar el teléfono con el cliente y el Sincronizador correctos. RutX central recibe la petición, valida sus credenciales y la reenvía por una conexión saliente persistente al Sincronizador correspondiente.

> **La aplicación conoce un solo dominio. El QR define a qué instalación pertenece. El Hub de RutX decide a qué Sincronizador se entrega cada operación.**

## Mapa de dominios

| Dirección | Consumidor | Responsabilidad |
|---|---|---|
| `https://plataform.rutx.quest` | Vendedores, soporte y administradores RutX | Clientes, licencias, instalaciones, inventario de dispositivos, QR y auditoría. |
| `https://rutx.quest` | Administradores de clientes | Portal multiempresa de clientes. |
| `https://rutx.quest/api/v1` | Apps móviles y Sincronizadores | API única para enrolamiento, autenticación, relay, licencias y acuses. |

No se crean subdominios por cliente, sucursal, teléfono o Sincronizador. La segmentación se ejecuta con `clientId`, `syncInstanceId`, `deviceId`, token de sesión y permisos.

## Lectura recomendada

| Prioridad | Documento | Para qué sirve |
|---:|---|---|
| 1 | [Arquitectura Hub/Relay central](arquitectura-hub-relay-rutx.md) | Comprender el flujo único desde una venta móvil hasta el Sincronizador correcto. |
| 2 | [Contrato MVP y JSON](license-contract-mvp.md) | Implementar los contratos de licencia, QR, conexión, login, venta y acuse. |
| 3 | [Combo operativo y permisos](nota-combo-operativo-permisos.md) | Consultar los permisos atómicos del paquete Ventas y su plan de implementación en Laravel, Flutter y .NET. |
| 4 | [Requerimientos de Plataforma RutX](requerimientos-plataforma-rutx.md) | Crear las pantallas, datos y capacidades mínimas de `plataform.rutx.quest`. |
| 5 | [Guía de app móvil](guia-implementacion-app-movil.md) | Crear la rama Flutter y adaptar enrolamiento, colas y llamadas a API. |
| 6 | [Guía del Sincronizador](guia-implementacion-sincronizador.md) | Crear la rama .NET y adaptar conexión persistente, relay y Extras.fdb. |
| 7 | [Red y puerto 5047](red-sincronizador-puerto-5047.md) | Configurar la PC sin IP pública ni *port-forwarding*. |
| 8 | [Modelo Extras.fdb](extras-fdb-model.md) | Persistir licencia, dispositivos y auditoría local. |
| 9 | [Configuración master](master-device-control.md) | Incremento posterior para reemplazo o baja de teléfonos. |
| 10 | [Compatibilidad del Sincronizador con Hub](compatibilidad-sincronizador-hub.md) | Distinguir la API pública única, las rutas locales .NET y el panel técnico `/admin`. |

## Una misma base, espacios de API distintos

El portal de clientes y la app móvil pueden compartir `rutx.quest` y el prefijo `/api/v1`, pero no comparten controlador ni sesión. La propuesta es `rutx.quest/api/v1/client/*` para el portal web y `rutx.quest/api/v1/mobile/*` para Flutter; el Hub encapsula las rutas .NET existentes en el puerto `5047`. El panel técnico local actual del Sincronizador (`/admin` y `/api/v2/admin/*`) no forma parte del portal web de clientes ni debe hacerse público. Véase la [revisión de compatibilidad](compatibilidad-sincronizador-hub.md).

## Límites del MVP

El teléfono conserva las ventas localmente cuando no tenga datos móviles o RutX no pueda entregar la operación. La app solo considera una venta enviada cuando recibe el acuse final del Sincronizador. RutX central no debe convertirse en la fuente contable ni escribir directamente en Microsip: funciona como **Hub de identidad y relay**; el Sincronizador conserva la responsabilidad de aplicar la venta en la instalación del cliente.

## Reglas que no deben cambiar

| Regla | Decisión |
|---|---|
| URL móvil | Siempre `https://rutx.quest/api/v1`; no se cambia por cliente. |
| Enrutamiento | El cliente no elige el destino en cada venta; el token de dispositivo enlazado determina la instalación. |
| Licencia | La LicenseKey es identificador comercial, no contraseña ni destino de red. |
| Dispositivo | Todo teléfono debe reclamar un QR temporal antes de iniciar sesión u operar. |
| Sincronizador | Se conecta saliendo a RutX; RutX no abre conexiones hacia la PC ni expone el router del cliente. |
| Ventas pendientes | Permanecen cifradas/locales en la app hasta recibir acuse exitoso. |
