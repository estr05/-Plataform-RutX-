<?php

/**
 * Configuración de integración con el Sincronizador (contrato /api/v2/web/*).
 *
 * Fuente única de la frontera: los controladores/Livewire nunca llaman al
 * Sincronizador directamente; usan App\Services\RutxApiClient.
 *
 * Seguridad:
 *  - Los secretos (client_secret) viven solo en el entorno, nunca en el repo.
 *  - verify_tls=true por defecto; jamás desactivarlo en producción.
 *  - Nunca versionar host/IP interno real (ver .env.example).
 */
return [

    // Base de la API web del Sincronizador.
    'base_url' => env('API_WEB_BASE_URL'),

    // Timeouts (segundos).
    'timeout' => (int) env('API_WEB_TIMEOUT', 30),
    'connect_timeout' => (int) env('API_WEB_CONNECT_TIMEOUT', 10),

    // Endpoint de token (client-credentials) y credenciales de cliente.
    'auth_url' => env('API_WEB_AUTH_URL'),
    'client_id' => env('API_WEB_CLIENT_ID'),
    'client_secret' => env('API_WEB_CLIENT_SECRET'),

    // Verificación TLS obligatoria.
    'verify_tls' => env('API_WEB_VERIFY_TLS', true),

    // Stubs de servicios mientras el Sincronizador no publique los endpoints
    // (forma exacta de los DTO del contrato). Desactivar al conectar lo real.
    'stubs_enabled' => env('API_WEB_STUBS_ENABLED', false),

    // TTL del token en caché (segundos). El Sincronizador puede devolver
    // expires_in; si lo hace, se respeta y esto es solo el respaldo.
    'token_cache_ttl' => (int) env('API_WEB_TOKEN_TTL', 300),

];
