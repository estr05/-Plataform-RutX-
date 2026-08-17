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
 * RutxHubClient — frontera HTTP central hacia el Hub/Relay central.
 *
 * Arquitectura acordada (sprint/2, revisión P0):
 *  - El navegador habla con Laravel; Laravel habla con el Hub (rutx.quest).
 *  - Laravel NUNCA se conecta a un Sincronizador por IP, DNS individual ni
 *    puerto :5047: el Hub resuelve la instalación por identidad registrada.
 *  - Frontera conceptual https://rutx.quest/api/v1. Mientras el contrato
 *    Relay no esté implementado, NO se definen endpoints nuevos ni se
 *    conecta un Hub real: stubs_enabled=true devuelve la forma de los DTO.
 *  - Autenticación servicio-a-servicio (client-credentials) con token en
 *    caché namespaced; TTL derivado de expires_in con margen de seguridad.
 *  - 401 → se descarta el token y se reintenta una sola vez (operaciones
 *    seguras/idempotentes). Escrituras futuras usarán idempotency key.
 */
class RutxHubClient
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
     * Token client-credentials en caché namespaced por base_url + client_id.
     */
    public function accessToken(): string
    {
        $key = $this->tokenCacheKey();

        if (($cached = Cache::get($key)) !== null) {
            return (string) $cached;
        }

        $data = $this->fetchToken();

        Cache::put($key, (string) $data['access_token'], $this->tokenTtl($data));

        return (string) $data['access_token'];
    }

    /**
     * Ejecuta una petición autenticada; traduce errores HTTP/timeout y
     * reintenta una sola vez ante un 401 (token revocado).
     *
     * @param  callable(PendingRequest): Response  $send
     * @return array<string, mixed>
     */
    private function request(callable $send, string $path, bool $retried = false): array
    {
        if ($this->config['stubs_enabled']) {
            return $this->stub($path);
        }

        try {
            $response = $send($this->http());
        } catch (ConnectionException $e) {
            throw new RutxApiException('No se pudo conectar con el Hub. Inténtelo más tarde.', 0, $e);
        }

        if ($response->unauthorized()) {
            // Token vencido o revocado: descartarlo y reintentar una vez.
            Cache::forget($this->tokenCacheKey());

            if (! $retried) {
                return $this->request($send, $path, retried: true);
            }

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
     * Solicita un token client-credentials al Hub.
     *
     * @return array<string, mixed>
     */
    private function fetchToken(): array
    {
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
            throw new RutxApiException('No se recibió un token válido del Hub.');
        }

        return $data;
    }

    /**
     * Clave de caché namespaced: hash de base_url + client_id (sin secretos).
     */
    private function tokenCacheKey(): string
    {
        $namespace = md5(($this->config['base_url'] ?? '').'|'.($this->config['client_id'] ?? ''));

        return 'rutx:hub:'.substr($namespace, 0, 16).':access_token';
    }

    /**
     * TTL del token: deriva de expires_in con margen de seguridad (60 s);
     * la configuración fija es solo respaldo cuando el Hub no lo envía.
     *
     * @param  array<string, mixed>  $data
     */
    private function tokenTtl(array $data): int
    {
        $expiresIn = (int) ($data['expires_in'] ?? 0);

        if ($expiresIn > 0) {
            return max(60, $expiresIn - 60);
        }

        return (int) $this->config['token_cache_ttl'];
    }

    /**
     * Respuesta stub — forma exacta del DTO del contrato (sin HTTP).
     * Se mantiene hasta que el contrato Relay esté implementado.
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
