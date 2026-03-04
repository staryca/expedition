<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Musician
{
    private const array TEXTS = [
        'гарманіст', 'гармонік', 'акардэон', 'баяніст', 'баян', 'паўбаян', 'трохрадка', 'скрыпак', 'скрыпка', 'скрыпкі',
        'бубен', 'мастацкі свіст', 'труба', 'флейта', 'барабан', 'цымбалы', 'мандаліна', 'балалайка', 'тарэлкі',
        'бутэлькі', 'ражок', 'бяроста', 'гітара', 'кларнет', 'квінтэт', 'квартэт', 'дудка', 'дудкі', 'рог', 'ражок',
        'шархуны', 'шумёлы', 'бразготкі', 'пішчык', 'цытра', 'лыжкі', 'саломка', 'музыкант', 'грае на ', 'ансамбль',
        'на грабяні', 'гуслі', 'арган',
    ];
    private const array SKIP = ['скрыпака', 'баяніста', 'гарманіста'];

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
