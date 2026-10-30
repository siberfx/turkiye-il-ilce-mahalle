<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Siberfx\TurkiyePackage\Enums\NeighborhoodType;

class NeighborhoodTypeTest extends TestCase
{
    public static function names(): array
    {
        return [
            'mahalle' => ['AHMET REMZİ YÜREĞİR MAHALLESİ', NeighborhoodType::Mahalle],
            'mahalle with number' => ['6 ŞUBAT MAHALLESİ', NeighborhoodType::Mahalle],
            'mahalle in a belde' => ['KÖMÜR BELDESİ, GAZİ MAHALLESİ', NeighborhoodType::Mahalle],
            'köy' => ['AKDURAK KÖYÜ', NeighborhoodType::Koy],
            'köy with apostrophe' => ["BOĞAZİÇİ KÖYÜ", NeighborhoodType::Koy],
            'mevkii of a köy' => ["BOĞAZİÇİ KÖYÜ, TAN'IN KOMLARI MEVKİİ", NeighborhoodType::Bagli],
            'mezra of a köy' => ['AKDURAK KÖYÜ, ÇAKMAK MEZRASI', NeighborhoodType::Bagli],
            'yayla evleri of a köy' => ['MUTLU KÖYÜ, TOZİK YAYLASI YAYLA EVLERİ', NeighborhoodType::Bagli],
            'official bağlı without suffix' => ['AKDURAK KÖYÜ, DAĞDİBİ', NeighborhoodType::Bagli],
            'bağlı of a former köy' => ['OSMANİYE MAHALLESİ, FATİH', NeighborhoodType::Bagli],
            'standalone mevkii' => ['PINARÇAYOSB MEVKİİ', NeighborhoodType::Bagli],
            'extra whitespace' => ['GENCELİ KÖYÜ ,  KÖY KONAĞI  MEVKİİ', NeighborhoodType::Bagli],
        ];
    }

    #[DataProvider('names')]
    public function test_type_is_derived_from_name(string $name, NeighborhoodType $expected): void
    {
        $this->assertSame($expected, NeighborhoodType::fromName($name));
    }

    public function test_values_are_stable(): void
    {
        // Stored in the database; changing them would break existing rows.
        $this->assertSame(['mahalle', 'koy', 'bagli'], array_column(NeighborhoodType::cases(), 'value'));
    }
}
