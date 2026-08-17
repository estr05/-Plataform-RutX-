<?php

namespace Tests\Feature;

use App\Exceptions\RutxApiException;
use App\Services\RutxApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RutxApiClientTest extends TestCase
{
    private function client(array $overrides = []): RutxApiClient
    {
        return new RutxApiClient(array_merge([
            'base_url' => 'https://sincronizador.test/api/v2/web',
            'timeout' => 30,
            'connect_timeout' => 10,
            'auth_url' => 'https://sincronizador.test/api/v2/web/auth',
            'client_id' => 'rutx-web-client',
            'client_secret' => 'secret',
            'verify_tls' => true,
            'stubs_enabled' => false,
            'token_cache_ttl' => 300,
        ], $overrides));
    }

    public function test_access_token_is_cached_and_reused(): void
    {
        Http::fake([
            'https://sincronizador.test/api/v2/web/auth' => Http::response([
                'access_token' => 'tok-123',
                'expires_in' => 3600,
            ]),
            'https://sincronizador.test/api/v2/web/*' => Http::response(['data' => []]),
        ]);

        $client = $this->client();
        $client->get('/customers');
        $client->get('/customers');

        // El token se pide una sola vez (caché) y las dos peticiones usan Bearer.
        Http::assertSentCount(3); // 1 auth + 2 peticiones
        Http::assertSent(fn ($request) => str_contains($request->url(), '/auth'));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer tok-123'));
    }

    public function test_request_sends_bearer_token_and_returns_json(): void
    {
        Http::fake([
            'https://sincronizador.test/api/v2/web/auth' => Http::response(['access_token' => 'tok-abc']),
            'https://sincronizador.test/api/v2/web/customers*' => Http::response([
                'data' => [['id' => 1, 'name' => 'Cliente A']],
                'meta' => ['total' => 1],
            ]),
        ]);

        $data = $this->client()->get('/customers', ['page' => 1]);

        $this->assertSame(1, $data['meta']['total']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/customers')
            && $request->hasHeader('Authorization', 'Bearer tok-abc'));
    }

    public function test_stubs_mode_skips_http(): void
    {
        Http::fake();

        $data = $this->client(['stubs_enabled' => true])->get('/customers');

        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(0, $data['meta']['total']);
        Http::assertNothingSent();
    }

    public function test_unauthorized_response_clears_token_and_throws(): void
    {
        Http::fake([
            'https://sincronizador.test/api/v2/web/auth' => Http::response(['access_token' => 'tok-exp'],
                200),
            'https://sincronizador.test/api/v2/web/*' => Http::response([], 401),
        ]);

        $client = $this->client();

        $this->expectException(RutxApiException::class);
        $client->get('/customers');

        $this->assertFalse(Cache::has('rutx.api.access_token'));
    }

    public function test_server_error_throws_friendly_exception(): void
    {
        Http::fake([
            'https://sincronizador.test/api/v2/web/auth' => Http::response(['access_token' => 'tok-1']),
            'https://sincronizador.test/api/v2/web/*' => Http::response([], 503),
        ]);

        try {
            $this->client()->get('/customers');
            $this->fail('Debería lanzar RutxApiException.');
        } catch (RutxApiException $e) {
            $this->assertSame(503, $e->getCode());
            $this->assertStringContainsString('Sincronizador', $e->getMessage());
        }
    }

    public function test_connection_timeout_throws_friendly_exception(): void
    {
        Http::fake([
            'https://sincronizador.test/api/v2/web/auth' => Http::response(['access_token' => 'tok-1']),
            'https://sincronizador.test/api/v2/web/*' => fn () => throw new ConnectionException('timeout'),
        ]);

        try {
            $this->client()->get('/customers');
            $this->fail('Debería lanzar RutxApiException.');
        } catch (RutxApiException $e) {
            $this->assertStringContainsString('No se pudo conectar', $e->getMessage());
        }
    }
}
