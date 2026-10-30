<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Siberfx\TurkiyePackage\Support\CityOrder;

class CityOrderTest extends TestCase
{
    public function test_turkish_alphabetical_order(): void
    {
        $names = ['ŞIRNAK', 'ÇORUM', 'IĞDIR', 'İZMİR', 'CANİK', 'ISPARTA', 'SİVAS', 'ÖDEMİŞ', 'ORDU', 'UŞAK', 'ÜRGÜP', 'ĞÜMÜŞ'];
        usort($names, CityOrder::compare(...));

        $this->assertSame(['CANİK', 'ÇORUM', 'ĞÜMÜŞ', 'IĞDIR', 'ISPARTA', 'İZMİR', 'ORDU', 'ÖDEMİŞ', 'SİVAS', 'ŞIRNAK', 'UŞAK', 'ÜRGÜP'], $names);
    }

    public function test_featured_cities_come_first_in_configured_order(): void
    {
        $cities = [1 => 'ADANA', 6 => 'ANKARA', 16 => 'BURSA', 17 => 'ÇANAKKALE', 34 => 'İSTANBUL', 35 => 'İZMİR', 20 => 'DENİZLİ'];

        $this->assertSame([34, 35, 16, 1, 6, 17, 20], CityOrder::order($cities, [34, 35, 16]));
    }

    public function test_unknown_featured_codes_are_ignored(): void
    {
        $this->assertSame([6, 1], CityOrder::order([1 => 'ADANA', 6 => 'ANKARA'], [99, 6]));
    }

    public function test_without_featured_cities_the_order_is_alphabetical(): void
    {
        $this->assertSame([1, 17, 20], CityOrder::order([20 => 'DENİZLİ', 17 => 'ÇANAKKALE', 1 => 'ADANA'], []));
    }
}
