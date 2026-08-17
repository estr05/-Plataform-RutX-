{{--
    <x-currency> — Monto MXN en JetBrains Mono, alineado a la derecha.

    Props:
      @prop float|int|string $amount  Monto a formatear (se castea a float).

    El formato vive en App\Support\Money::format() (fuente única).
--}}
@props(['amount'])

<span class="font-mono text-[14px] text-right whitespace-nowrap">
    {{ \App\Support\Money::format((float) $amount) }}
</span>
