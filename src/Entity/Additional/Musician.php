<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Musician
{
    private array $texts = [
        'гарманіст', 'грае на ', 'гармонік', 'акардэон', 'баяніст', 'баян', 'скрыпак', 'скрыпка', 'музыкант', 'лыжкі',
        'бубен', 'мастацкі свіст', 'труба', 'флейта', 'барабан',
    ];
    private array $skip = ['скрыпака', 'баяніста', 'гарманіста'];

    public function hasMusicianText(string $text): bool
    {
        $text = mb_strtolower($text);

        foreach ($this->texts as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return true;
            }
        }

        return false;
    }

    public function isMusician(?string $text): ?bool
    {
        if (empty($text)) {
            return null;
        }

        $text = mb_strtolower($text);

        foreach ($this->skip as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return null;
            }
        }

        foreach ($this->texts as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return true;
            }
        }

        return null;
    }
}
