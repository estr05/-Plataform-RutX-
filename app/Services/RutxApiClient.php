<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RutxApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * RutxApiClient — cliente HTTP central hacia el Sincronizador.
 *
 * Reglas de la frontera (guidelines §1.4):
 *  - El navegador habla con Laravel; Laravel habla con el Sincronizador.
 *  - Ningún controlador/Livewire llama al Sincronizador directamente.
 *  - Autenticación client-credentials (client_id/client_secret) con token
 *    en caché; el token nunca viaja a la sesión ni a la respuesta web.
 *  - Timeouts, verificación TLS y errores traducidos (RutxApiException).
 *
 * Mientras el Sincronizador no publique /api/v2/web/*, stubs_enabled=true
 * devuelve la forma exacta de los DTO sin hacer HTTP.
 */
class RutxApiClient
{
    /**
     * @param  array<string, mixed>  $config  config('rutx')
     */
    public function __construct(private readonly array $config) {}

    public function get(string $path, array $params = []): array
    {
        return $this->request(fn (PendingRequest $http) => $http->get($path, $params), $path);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request(fn (PendingRequest $http) => $http->post($path, $body), $path);
    }

    /**
     * Token de acceso client-credentials, cacheados para reutilizar.
     */
    public function accessToken(): string
    {
        return Cache::remember('rutx.api.access_token', $this->config['token_cache_ttl'], function (): string {
            $response = Http::timeout($this->config['timeout'])
                ->connectTimeout($this->config['connect_timeout'])
                ->withOptions(['verify' => (bool) $this->config['verify_tls']])
                ->asForm()
                ->post($this->config['auth_url'], [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                ]);

            if ($response->unauthorized() || $response->forbidden()) {
                throw RutxApiException::fromResponse('auth', $response);
            }

            $data = $response->json();

            if (! is_array($data) || empty($data['access_token'])) {
                throw new RutxApiException('No se recibió un token válido del Sincronizador.');
            }

            return (string) $data['access_token'];
        });
    }

    /**
     * Ejecuta una petición autenticada y traduce errores HTTP/timeout.
     *
     * @param  callable(PendingRequest): Response  $send
     * @return array<string, mixed>
     */
    private function request(callable $send, string $path): array
    {
        if ($this->config['stubs_enabled']) {
            return $this->stub($path);
        }

        try {
            $response = $send($this->http());
        } catch (ConnectionException $e) {
            throw new RutxApiException('No se pudo conectar con el Sincronizador. Inténtelo más tarde.', 0, $e);
        }

        if ($response->unauthorized()) {
            // Token vencido o revocado: descartarlo y reportar.
            Cache::forget('rutx.api.access_token');

            throw RutxApiException::fromResponse('request', $response);
        }

        if ($response->clientError() || $response->serverError()) {
            throw RutxApiException::fromResponse('request', $response);
        }

        return $response->json() ?? [];
    }

    /**
     * PendingRequest autenticado con la configuración de frontera.
     */
    private function http(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['timeout'])
            ->connectTimeout($this->config['connect_timeout'])
            ->withOptions(['verify' => (bool) $this->config['verify_tls']])
            ->withToken($this->accessToken())
            ->acceptJson()
            ->asJson();
    }

    /**
     * Respuesta stub — forma exacta del DTO del contrato v2 (sin HTTP).
     *
     * @return array<string, mixed>
     */
    private function stub(string $path): array
    {
        return match (true) {
            str_contains($path, 'customers') => [
                'data' => [],
                'meta' => ['total' => 0, 'page' => 1, 'per_page' => 25],
            ],
            default => [
                'data' => [],
                'meta' => ['total' => 0],
            ],
        };
    }
}
