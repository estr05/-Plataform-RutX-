<?php

/**
 * Configuración de la frontera con el Hub/Relay central.
 *
 * Arquitectura (sprint/2, revisión P0): Laravel habla con el Hub en
 * https://rutx.quest/api/v1; el Hub resuelve la instalación por identidad.
 * Laravel NUNCA usa IP, DNS individual ni el puerto :5047 de un
 * Sincronizador. Los controladores/Livewire usan App\Services\RutxHubClient.
 *
 * Estado actual: frontera conceptual. NO conectar todavía contra un Hub
 * real: el contrato Relay aún no está implementado. Mientras tanto, los
 * endpoints no se inventan y los stubs devuelven la forma exacta de los
 * DTO sin HTTP.
 *
 * Stubs por entorno (sprint/3): local y testing fuerzan
 * RUTX_HUB_STUBS_ENABLED=true (por defecto y en .env.example/phpunit.xml)
 * para que desarrollo y pruebas NUNCA llamen al Hub real por accidente.
 * En staging/production el valor debe ser false SOLO cuando exista un Hub
 * disponible y las credenciales se hayan inyectado como secretos. Si los
 * stubs están desactivados y falta RUTX_HUB_CLIENT_ID, RUTX_HUB_CLIENT_SECRET
 * o una URL HTTPS válida, la primera solicitud falla de forma explícita:
 * la configuración no infiere silenciosamente una conexión real.
 *
 * Seguridad:
 *  - client_secret vive solo en el entorno, nunca en el repo.
 *  - verify_tls=true por defecto; jamás desactivarlo en producción.
 */
return [

    // Base conceptual del Hub (personal de plataforma).
    'base_url' => env('RUTX_HUB_BASE_URL', 'https://rutx.quest/api/v1'),

    // Timeouts (segundos).
    'timeout' => (int) env('RUTX_HUB_TIMEOUT', 30),
    'connect_timeout' => (int) env('RUTX_HUB_CONNECT_TIMEOUT', 10),

    // Endpoint de token (client-credentials) y credenciales de cliente.
    'auth_url' => env('RUTX_HUB_AUTH_URL', 'https://rutx.quest/api/v1/auth'),
    'client_id' => env('RUTX_HUB_CLIENT_ID'),
    'client_secret' => env('RUTX_HUB_CLIENT_SECRET'),

    // Verificación TLS obligatoria.
    'verify_tls' => env('RUTX_HUB_VERIFY_TLS', true),

    // Stubs de servicios hasta que el contrato Relay esté implementado.
    // local/testing activan stubs por defecto; solo staging/production
    // pueden desactivarlos y, aun así, exigen credenciales de entorno.
    'stubs_enabled' => env('RUTX_HUB_STUBS_ENABLED', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)),

    // TTL de respaldo del token (segundos); expires_in del Hub lo reemplaza.
    'token_cache_ttl' => (int) env('RUTX_HUB_TOKEN_TTL', 300),

];
