<?php

namespace Tests\Feature;

use App\Exceptions\RutxApiException;
use App\Services\RutxHubClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RutxHubClientTest extends TestCase
{
    private function client(array $overrides = []): RutxHubClient
    {
        return new RutxHubClient(array_merge([
            'base_url' => 'https://rutx.quest/api/v1',
            'timeout' => 30,
            'connect_timeout' => 10,
            'auth_url' => 'https://rutx.quest/api/v1/auth',
            'client_id' => 'rutx-web-client',
            'client_secret' => 'secret',
            'verify_tls' => true,
            'stubs_enabled' => false,
            'token_cache_ttl' => 300,
        ], $overrides));
    }

    private function fakeHub(array $customers = []): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response([
                'access_token' => 'tok-123',
                'expires_in' => 3600,
            ]),
            'https://rutx.quest/api/v1/customers*' => Http::response($customers ?: ['data' => []]),
        ]);
    }

    public function test_access_token_is_cached_and_reused(): void
    {
        $this->fakeHub();

        $client = $this->client();
        $client->get('/customers');
        $client->get('/customers');

        // El token se pide una sola vez (caché) y las peticiones usan Bearer.
        Http::assertSentCount(3); // 1 auth + 2 peticiones
        Http::assertSent(fn ($request) => str_contains($request->url(), '/auth'));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer tok-123'));
    }

    public function test_token_cache_key_is_namespaced(): void
    {
        $this->fakeHub();

        $this->client()->get('/customers');

        $key = 'rutx:hub:'.substr(md5('https://rutx.quest/api/v1|rutx-web-client'), 0, 16).':access_token';

        $this->assertTrue(Cache::has($key), 'La clave de caché debe estar namespaced (base_url + client_id).');
        $this->assertSame('tok-123', Cache::get($key));
    }

    public function test_token_ttl_derives_from_expires_in(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response([
                'access_token' => 'tok-corto',
                'expires_in' => 120, // ttl = 120 - 60 = 60 s (no el respaldo de 300)
            ]),
            'https://rutx.quest/api/v1/customers*' => Http::response(['data' => []]),
        ]);

        $client = $this->client(['token_cache_ttl' => 300]);
        $client->get('/customers');
        $client->get('/customers');

        // Sin re-fetch: el token sigue vigente dentro de su TTL derivado.
        Http::assertSentCount(3);
    }

    public function test_request_sends_bearer_token_and_returns_json(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-abc']),
            'https://rutx.quest/api/v1/customers*' => Http::response([
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

    public function test_stubs_are_enabled_by_default_in_testing_via_container(): void
    {
        // phpunit.xml y tests/bootstrap.php fuerzan RUTX_HUB_STUBS_ENABLED=true:
        // el singleton del contenedor (config('rutx')) debe devolver DTO sin HTTP.
        Http::fake();

        $client = app(RutxHubClient::class);
        $data = $client->get('/customers');

        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(0, $data['meta']['total']);
        Http::assertNothingSent();
    }

    public function test_disabled_stubs_without_credentials_fails_explicitly(): void
    {
        Http::fake();

        $client = $this->client([
            'stubs_enabled' => false,
            'client_id' => '',
            'client_secret' => '',
        ]);

        try {
            $client->get('/customers');
            $this->fail('Debería fallar explícitamente sin credenciales.');
        } catch (RutxApiException $e) {
            $this->assertStringContainsString('RUTX_HUB_CLIENT_ID', $e->getMessage());
            $this->assertStringContainsString('RUTX_HUB_CLIENT_SECRET', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_disabled_stubs_without_https_url_fails_explicitly(): void
    {
        Http::fake();

        $client = $this->client([
            'stubs_enabled' => false,
            'base_url' => 'http://rutx.quest/api/v1',
            'auth_url' => 'http://rutx.quest/api/v1/auth',
        ]);

        try {
            $client->get('/customers');
            $this->fail('Debería fallar explícitamente con URL no HTTPS.');
        } catch (RutxApiException $e) {
            $this->assertStringContainsString('RUTX_HUB_BASE_URL', $e->getMessage());
            $this->assertStringContainsString('RUTX_HUB_AUTH_URL', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_unauthorized_retries_once_with_fresh_token(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-fresh']),
            'https://rutx.quest/api/v1/customers*' => Http::sequence()
                ->push([], 401) // token revocado
                ->push(['data' => ['ok' => true]], 200), // reintento con token fresco
        ]);

        $data = $this->client()->get('/customers');

        $this->assertTrue($data['data']['ok']);
        // auth + 401 + re-auth (token fresco) + reintento = 4 llamadas.
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/customers')
            && $request->hasHeader('Authorization', 'Bearer tok-fresh'));
    }

    public function test_unauthorized_twice_throws_and_clears_token(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-x']),
            'https://rutx.quest/api/v1/customers*' => Http::response([], 401),
        ]);

        $client = $this->client();

        $this->expectException(RutxApiException::class);
        $client->get('/customers');

        $this->assertFalse(Cache::has('rutx:hub:'.substr(md5('https://rutx.quest/api/v1|rutx-web-client'), 0, 16).':access_token'));
    }

    public function test_post_without_idempotency_key_is_rejected_before_sending(): void
    {
        Http::fake();

        try {
            $this->client()->post('/customers', ['name' => 'Cliente X']);
            $this->fail('Una escritura sin Idempotency-Key debe rechazarse.');
        } catch (RutxApiException $e) {
            $this->assertStringContainsString('Idempotency-Key', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_post_sends_idempotency_key_header(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-abc']),
            'https://rutx.quest/api/v1/customers*' => Http::response(['data' => ['id' => 7]], 201),
        ]);

        $data = $this->client()->post('/customers', ['name' => 'Cliente X'], 'key-0001');

        $this->assertSame(7, $data['data']['id']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/customers')
            && $request->hasHeader('Idempotency-Key', 'key-0001'));
    }

    public function test_post_with_key_retries_once_with_same_key_after_401(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-fresh']),
            'https://rutx.quest/api/v1/customers*' => Http::sequence()
                ->push([], 401) // token revocado: reintento permitido por la clave
                ->push(['data' => ['id' => 7]], 201),
        ]);

        $data = $this->client()->post('/customers', ['name' => 'Cliente X'], 'key-0001');

        $this->assertSame(7, $data['data']['id']);
        // auth + 401 + re-auth + reintento = 4; ambos POST llevan la MISMA clave.
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/customers')
            && $request->hasHeader('Idempotency-Key', 'key-0001'));
    }

    public function test_post_without_key_never_retries(): void
    {
        // El rechazo ocurre antes de cualquier HTTP: ni siquiera se pide token.
        Http::fake();

        try {
            $this->client()->post('/customers', ['name' => 'Cliente X']);
            $this->fail('Debería rechazarse la escritura sin clave.');
        } catch (RutxApiException) {
            // esperado
        }

        Http::assertNothingSent();
    }

    public function test_conflict_409_throws_without_retry(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-1']),
            // Mismo key + cuerpo distinto: el Hub responde 409 y nunca procesa la segunda escritura.
            'https://rutx.quest/api/v1/customers*' => Http::response(['error' => 'conflict'], 409),
        ]);

        try {
            $this->client()->post('/customers', ['name' => 'Otro'], 'key-0001');
            $this->fail('Debería lanzar RutxApiException por conflicto de idempotencia.');
        } catch (RutxApiException $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertStringContainsString('idempotencia', strtolower($e->getMessage()));
        }

        // auth + 1 POST: sin reintento.
        Http::assertSentCount(2);
    }

    public function test_new_idempotency_key_is_uuid(): void
    {
        $key = $this->client()->newIdempotencyKey();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $key
        );
    }

    public function test_server_error_throws_friendly_exception(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-1']),
            'https://rutx.quest/api/v1/customers*' => Http::response([], 503),
        ]);

        try {
            $this->client()->get('/customers');
            $this->fail('Debería lanzar RutxApiException.');
        } catch (RutxApiException $e) {
            $this->assertSame(503, $e->getCode());
            $this->assertStringContainsString('Hub', $e->getMessage());
        }
    }

    public function test_connection_timeout_throws_friendly_exception(): void
    {
        Http::fake([
            'https://rutx.quest/api/v1/auth' => Http::response(['access_token' => 'tok-1']),
            'https://rutx.quest/api/v1/customers*' => fn () => throw new ConnectionException('timeout'),
        ]);

        try {
            $this->client()->get('/customers');
            $this->fail('Debería lanzar RutxApiException.');
        } catch (RutxApiException $e) {
            $this->assertStringContainsString('No se pudo conectar', $e->getMessage());
        }
    }
}
