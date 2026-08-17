<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Construye notificaciones efímeras para la interfaz web de RutX.
 *
 * Equivale a feedback_utils.dart: cada llamada reemplaza la notificación
 * vigente, igual que ScaffoldMessenger.hideCurrentSnackBar().
 */
final class Feedback
{
    private const DEFAULT_DURATION = 3_000;

    private const ERROR_DURATION = 5_000;

    /** @var list<string> */
    private const TYPES = ['success', 'info', 'warning', 'error'];

    public static function success(string $message): array
    {
        return self::make(
            type: 'success',
            message: $message,
            duration: self::DEFAULT_DURATION,
        );
    }

    public static function info(
        string $message,
        ?string $actionLabel = null,
        ?string $actionEvent = null,
    ): array {
        return self::make(
            type: 'info',
            message: $message,
            duration: self::DEFAULT_DURATION,
            actionLabel: $actionLabel,
            actionEvent: $actionEvent,
        );
    }

    public static function warning(
        string $message,
        ?string $title = null,
        ?string $actionLabel = null,
        ?string $actionEvent = null,
        int $duration = self::ERROR_DURATION,
    ): array {
        return self::make(
            type: 'warning',
            message: $message,
            title: $title,
            duration: $duration,
            actionLabel: $actionLabel,
            actionEvent: $actionEvent,
        );
    }

    /**
     * Equivalente de showError de Flutter.
     *
     * El botón «REINTENTAR» solo se muestra cuando el error es recuperable y
     * existe un evento de aplicación autorizado para manejarlo.
     */
    public static function error(
        string $message,
        bool $isRecoverable = false,
        ?string $retryEvent = null,
    ): array {
        return self::make(
            type: 'error',
            title: 'Error',
            message: $message,
            duration: self::ERROR_DURATION,
            actionLabel: $isRecoverable && $retryEvent !== null ? 'REINTENTAR' : null,
            actionEvent: $isRecoverable ? $retryEvent : null,
        );
    }

    /** Equivalente de showErrorMessage de Flutter. */
    public static function errorMessage(string $message): array
    {
        return self::make(
            type: 'error',
            title: 'Error',
            message: $message,
            duration: self::ERROR_DURATION,
        );
    }

    /**
     * Conserva el feedback para la siguiente respuesta HTTP y reemplaza
     * cualquier notificación anterior pendiente, como el Snackbar de Flutter.
     * Úsese desde controladores antes de un redirect.
     *
     * @param  array<string, mixed>  $feedback
     */
    public static function flash(array $feedback): void
    {
        session()->flash('rutx_feedback', self::validated($feedback));
    }

    /**
     * Normaliza datos de sesión antes de exponerlos al navegador.
     * Un valor inválido se descarta en lugar de llegar a la UI.
     *
     * @return array<string, mixed>|null
     */
    public static function fromSession(mixed $feedback): ?array
    {
        if (! is_array($feedback)) {
            return null;
        }

        try {
            return self::validated($feedback);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array{type: string, title: ?string, message: string, duration: int, action: ?array{label: string, event: string}}
     */
    private static function make(
        string $type,
        string $message,
        ?string $title = null,
        int $duration = self::DEFAULT_DURATION,
        ?string $actionLabel = null,
        ?string $actionEvent = null,
    ): array {
        return self::validated([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'duration' => $duration,
            'action' => $actionLabel !== null || $actionEvent !== null
                ? ['label' => $actionLabel, 'event' => $actionEvent]
                : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $feedback
     * @return array{type: string, title: ?string, message: string, duration: int, action: ?array{label: string, event: string}}
     */
    private static function validated(array $feedback): array
    {
        $type = $feedback['type'] ?? null;
        $message = $feedback['message'] ?? null;
        $title = $feedback['title'] ?? null;
        $duration = $feedback['duration'] ?? self::DEFAULT_DURATION;
        $action = $feedback['action'] ?? null;

        if (! is_string($type) || ! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('El tipo de feedback no es válido.');
        }

        if (! is_string($message) || trim($message) === '') {
            throw new InvalidArgumentException('El mensaje de feedback es obligatorio.');
        }

        if ($title !== null && (! is_string($title) || trim($title) === '')) {
            throw new InvalidArgumentException('El título de feedback no es válido.');
        }

        if (! is_int($duration) || $duration < 1_000 || $duration > 30_000) {
            throw new InvalidArgumentException('La duración de feedback debe estar entre 1 y 30 segundos.');
        }

        if ($action === null) {
            $normalizedAction = null;
        } elseif (
            is_array($action)
            && is_string($action['label'] ?? null)
            && trim($action['label']) !== ''
            && is_string($action['event'] ?? null)
            && preg_match('/^[a-z][a-z0-9._-]{0,79}$/', $action['event']) === 1
        ) {
            $normalizedAction = [
                'label' => trim($action['label']),
                'event' => $action['event'],
            ];
        } else {
            throw new InvalidArgumentException('La acción de feedback no es válida.');
        }

        return [
            'type' => $type,
            'title' => is_string($title) ? trim($title) : null,
            'message' => trim($message),
            'duration' => $duration,
            'action' => $normalizedAction,
        ];
    }
}
