<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Error de integración con el Sincronizador.
 *
 * Mensajes en español de México, orientados a usuario final; la traza
 * original (requestId, endpoint, status) se registra en logs sin secretos.
 */
class RutxApiException extends RuntimeException
{
    public static function fromResponse(string $context, Response $response): self
    {
        $status = $response->status();

        $message = match (true) {
            $status === 401 || $status === 403 => 'El Hub rechazó la autenticación o el permiso.',
            $status >= 500 => 'El Hub reportó un error interno.',
            default => 'El Hub devolvió una respuesta inesperada.',
        };

        return new self($message, $status);
    }
}
