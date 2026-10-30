<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Enums;

enum NeighborhoodType: string
{
    /** Mahalle, including mahalles inside a belde */
    case Mahalle = 'mahalle';

    /** Köy (village) */
    case Koy = 'koy';

    /** Bağlı: a mevkii, mezra, yayla evleri, ... attached to a köy */
    case Bagli = 'bagli';

    /**
     * Derive the type from a dump name: "X MAHALLESİ", "B BELDESİ, X MAHALLESİ",
     * "X KÖYÜ" or "X KÖYÜ, Y ..." (a bağlı of köy X).
     */
    public static function fromName(string $name): self
    {
        [$head, $rest] = array_map(trim(...), explode(',', $name, 2)) + [1 => null];

        if ($rest === null) {
            return match (true) {
                str_ends_with($head, ' KÖYÜ') => self::Koy,
                str_ends_with($head, ' MAHALLESİ') => self::Mahalle,
                default => self::Bagli,
            };
        }

        return str_ends_with($head, ' BELDESİ') ? self::Mahalle : self::Bagli;
    }
}
