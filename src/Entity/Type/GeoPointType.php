<?php

declare(strict_types=1);

namespace App\Entity\Type;

class GeoPointType
{
    public const string NAME = 'geo_point';

    public const string BE_AGRO_CITY = 'аграгарадок';
    public const string BE_VILLAGE = 'вёска';
    public const string BE_OLD_VILLAGE = 'былая вёска';
    public const string BE_TOWN = 'горад';
    public const string BE_SETTLEMENT = 'пасёлак';
    public const string BE_URBAN_SETTLEMENT = 'гарадскі пасёлак';
    public const string BE_RESORT_SETTLEMENT = 'курортны пасёлак';
    public const string BE_WORKER_SETTLEMENT = 'рабочы пасёлак';
    public const string BE_SNP = 'снп';
    public const string BE_TRACT = 'урочышча';
    public const string BE_FOLWARK = 'фальварак';
    public const string BE_KHUTOR = 'хутар';

    public const string BE_AGRO_CITY_SHORT = 'аг';
    public const string BE_VILLAGE_SHORT = 'в';
    public const string BE_OLD_VILLAGE_SHORT = 'в';
    public const string BE_TOWN_SHORT = 'г';
    public const string BE_SETTLEMENT_SHORT = 'п';
    public const string BE_URBAN_SETTLEMENT_SHORT = 'гп';
    public const string BE_RESORT_SETTLEMENT_SHORT = 'кп';
    public const string BE_WORKER_SETTLEMENT_SHORT = 'рп';
    public const string BE_TRACT_SHORT = 'ур';
    public const string BE_FOLWARK_SHORT = 'ф';
    public const string BE_KHUTOR_SHORT = 'х';

    public const array BE_SHORT_LONG = [
        self::BE_AGRO_CITY_SHORT => self::BE_AGRO_CITY,
        self::BE_VILLAGE_SHORT => self::BE_VILLAGE,
        self::BE_TOWN_SHORT => self::BE_TOWN,
        self::BE_SETTLEMENT_SHORT => self::BE_SETTLEMENT,
        self::BE_URBAN_SETTLEMENT_SHORT => self::BE_URBAN_SETTLEMENT,
        self::BE_RESORT_SETTLEMENT_SHORT => self::BE_RESORT_SETTLEMENT,
        self::BE_WORKER_SETTLEMENT_SHORT => self::BE_WORKER_SETTLEMENT,
        self::BE_TRACT_SHORT => self::BE_TRACT,
        self::BE_FOLWARK_SHORT => self::BE_FOLWARK,
        self::BE_KHUTOR_SHORT => self::BE_KHUTOR,
    ];

    public const array BE_VILLAGE_LONGS = [
        self::BE_VILLAGE,
        self::BE_OLD_VILLAGE,
        self::BE_AGRO_CITY,
        self::BE_URBAN_SETTLEMENT,
        self::BE_WORKER_SETTLEMENT,
        self::BE_SETTLEMENT,
        self::BE_TOWN,
    ];

    public const array BE_SETTLEMENT_LONGS = [
        self::BE_SETTLEMENT,
        self::BE_URBAN_SETTLEMENT,
    ];

    public static function getShortName(?string $longName): string
    {
        if (empty($longName)) {
            return '';
        }

        $beShort = array_search($longName, self::BE_SHORT_LONG, true);
        if (false === $beShort) {
            if (self::BE_OLD_VILLAGE === $longName) {
                return self::BE_OLD_VILLAGE_SHORT . '.';
            }

            return mb_substr($longName, 0, 1) . '.';
        }

        if (in_array($beShort, [self::BE_URBAN_SETTLEMENT_SHORT, self::BE_RESORT_SETTLEMENT, self::BE_WORKER_SETTLEMENT_SHORT], true)) {
            return mb_substr($beShort, 0, 1) . '.' . mb_substr($beShort, 1, 1) . '.';
        }

        return $beShort . '.';
    }
}
