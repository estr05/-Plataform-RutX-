<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formateo monetario único de RutX Web (MXN).
 *
 * <x-currency> y Money::format() producen exactamente el mismo formato
 * ("$ 1,234.56") para que no existan dos maneras de mostrar un monto
 * (guidelines §5.2: montos en JetBrains Mono, alineados a la derecha).
 */
final class Money
{
    public static function format(float $amount): string
    {
        return '$ '.number_format($amount, 2, '.', ',');
    }
}
