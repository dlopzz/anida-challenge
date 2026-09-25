<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Objeto de valor: el resultado de evaluar una solicitud, siempre con sus motivos.
 */
final class Decision
{
    /** @param Motivo[] $motivos */
    private function __construct(
        public readonly Estado $estado,
        public readonly array $motivos,
    ) {
    }

    public static function aprobada(): self
    {
        return new self(Estado::APROBADA, []);
    }

    public static function rechazada(Motivo ...$motivos): self
    {
        return new self(Estado::RECHAZADA, $motivos);
    }

    public static function revisionManual(Motivo ...$motivos): self
    {
        return new self(Estado::REVISION_MANUAL, $motivos);
    }

    /** Para reconstruir una decisión guardada. @param Motivo[] $motivos */
    public static function reconstruir(Estado $estado, array $motivos): self
    {
        return new self($estado, $motivos);
    }

    /** @return string[] */
    public function motivosComoTexto(): array
    {
        return array_map(fn (Motivo $motivo) => $motivo->value, $this->motivos);
    }
}
