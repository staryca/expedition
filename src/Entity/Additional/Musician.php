<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Musician
{
    private const TEXTS = [
        'гарманіст', 'грае на ', 'гармонік', 'акардэон', 'баяніст', 'баян', 'скрыпак', 'скрыпка', 'музыкант', 'лыжкі',
        'бубен', 'мастацкі свіст', 'труба', 'флейта', 'барабан', 'цымбалы',
    ];
    private const SKIP = ['скрыпака', 'баяніста', 'гарманіста'];

    public static function hasMusicianText(string $text): bool
    {
        $text = mb_strtolower($text);

        foreach (self::TEXTS as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return true;
            }
        }

        return false;
    }

    public static function isMusician(?string $text): ?bool
    {
        if (empty($text)) {
            return null;
        }

        $text = mb_strtolower($text);

        foreach (self::SKIP as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return null;
            }
        }

        foreach (self::TEXTS as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return true;
            }
        }

        return null;
    }
}
