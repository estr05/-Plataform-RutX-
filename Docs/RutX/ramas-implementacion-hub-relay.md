# Ramas sugeridas — Implementación Hub/Relay RutX

Las ramas no mezclan licenciamiento, Relay y UI final. Cada una debe integrarse sólo después de pruebas de contrato y revisión técnica.

| Repositorio | Rama | Alcance cerrado |
|---|---|---|
| Web-RutX / Laravel | `feature/rutx-license-hub-core` | Tablas de clientes/licencias/instalaciones, Ed25519, activación, tokens QR y auditoría. |
| Web-RutX / Laravel | `feature/rutx-relay-hub` | Sesiones de Sincronizador, presencia, enrutamiento autenticado, entregas, acuses e idempotencia central. |
| Web-RutX / Laravel | `feature/rutx-platform-license-ops` | Pantallas de personal RutX: clientes, licencias, instalaciones, QR, dispositivos, auditoría. |
| RutX-AppMovil | `feature/rutx-hub-relay-mobile` | Enrolamiento QR, identidad de dispositivo, tokens seguros, API única y cola durable de ventas. |
| RutX-AppMovil | `feature/rutx-master-device-controls` | Configuración master, baja y reemplazo, posterior al enrolamiento estable. |
| RutX-Sincronizador | `feature/rutx-hub-relay-synchronizer` | Activación, Relay saliente, QR, cupos, licencia firmada, dispatcher e idempotencia. |
| RutX-Sincronizador | `feature/rutx-relay-observability` | Métricas de conexión, logs correlacionados y recuperación de operaciones. |

## Orden recomendado

1. `feature/rutx-license-hub-core`
2. `feature/rutx-hub-relay-synchronizer` hasta activación, sesión Relay y chequeo firmado
3. `feature/rutx-hub-relay-mobile` hasta enrolamiento QR y login
4. `feature/rutx-relay-hub` y envío de una operación de prueba end-to-end
5. Cola durable de ventas e idempotencia en app y Sincronizador
6. UI de Plataforma RutX y controles master

## Prueba transversal de salida

Usar Coyatoc Matriz como caso controlado: una licencia activa con siete cupos, un Sincronizador conectado, dos teléfonos enrolados y una venta creada sin red antes de restablecer datos. La operación debe llegar una sola vez a Microsip y dejar trazabilidad completa en Hub, Sincronizador y app.

