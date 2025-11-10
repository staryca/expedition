<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Artist
{
    public const CHILDREN = 'дзеці';

    public const CHILDREN_NAME = 'Выконваюць дзеці';

    public const CHILDREN_PLAYLIST = 'PLKw5hnive9Zq1i0ngESJev9MAPOZUZ-e4';

    public static function isChildren(?string $text): bool
    {
        $text = mb_strtolower(trim((string) $text));

        return !empty($text) && ($text === static::CHILDREN || str_starts_with(static::CHILDREN, $text));
    }
}
