<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Bootstrap de pruebas — entorno hermético
|--------------------------------------------------------------------------
|
| Laravel lee las variables de entorno con prioridad sobre $_SERVER, pero
| PHPUnit solo puede sobrescribir $_ENV y getenv (no $_SERVER). Cuando la
| máquina o la terminal inyectan variables de .env al proceso (p. ej. Git
| Bash en Windows pone SESSION_PATH=C:/Program Files/Git/, o una terminal
| carga .env con SESSION_DRIVER=database o CACHE_STORE=database), los
| valores de prueba de phpunit.xml quedan eclipsados en $_SERVER y los
| tests rompen (driver de sesión sin tabla, cache sin tabla, etc.).
|
| Este bootstrap se ejecuta antes de que la aplicación arranque y fija en
| $_SERVER/$_ENV/getenv el mismo entorno que declara phpunit.xml. En
| sistemas sin el quirk replica exactamente phpunit.xml (no-op).
*/
$testEnv = [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:jUqK38bYvPXZj7u8qJ+2zQw9n8B3v7yX1tQ5wV1PqUo=',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'SESSION_PATH' => '/',
    'RUTX_HUB_STUBS_ENABLED' => 'true',
    'PULSE_ENABLED' => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED' => 'false',
];

foreach ($testEnv as $name => $value) {
    $_SERVER[$name] = $value;
    $_ENV[$name] = $value;
    putenv("{$name}={$value}");
}
