<?php

declare(strict_types=1);

namespace App\Aplicacion;

use App\Dominio\Solicitud;

/** Lo que sale por la API. `repetida` distingue una solicitud nueva de un reintento. */
final class ResultadoEvaluacion
{
    /** @param string[] $motivos */
    private function __construct(
        public readonly string $id,
        public readonly string $decision,
        public readonly int $cuota,
        public readonly array $motivos,
        public readonly bool $repetida,
    ) {
    }

    public static function desde(Solicitud $solicitud, bool $repetida): self
    {
        return new self(
            $solicitud->id,
            $solicitud->decision->estado->value,
            $solicitud->cuota->centavos(),
            $solicitud->decision->motivosComoTexto(),
            $repetida,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'decision' => $this->decision,
            'cuota' => $this->cuota,
            'motivos' => $this->motivos,
        ];
    }
}
