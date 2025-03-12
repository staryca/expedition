<?php

declare(strict_types=1);

namespace App\Entity\Type;

class OrganizationType
{
    public const BAND = 1;
    public const COLLECTIVE = 2;
    public const MUSEUM = 3;
    public const CULTURE_HOUSE = 4;

    public const TYPES = [
        self::BAND => 'Гурт, хор, гурток',
        self::COLLECTIVE => 'Калектыў, ансамбль, група',
        self::MUSEUM => 'Музей',
        self::CULTURE_HOUSE => 'Дом культуры, дом рамёстваў',
    ];
}
