<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Artist
{
    public const CHILDREN = 'дзеці';

    public const CHILDREN_NAME = 'Выконваюць дзеці';

    public const CHILDREN_PLAYLIST = '';

    public function isChildren(string $text): bool
    {
        $text = mb_strtolower(trim($text));

        return $text === static::CHILDREN || str_starts_with(static::CHILDREN, $text);
    }
}
