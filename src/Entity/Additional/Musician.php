<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Musician
{
    private array $texts = [
        'гарманіст', 'грае на ', 'гармонік', 'акардэон', 'баяніст', 'баян', 'скрыпак', 'скрыпка', 'музыкант', 'лыжкі',
        'бубен', 'мастацкі свіст', 'труба', 'флейта',
    ];
    private array $skip = ['скрыпака', 'баяніста', 'гарманіста'];

    public function isMusician(string $text): ?bool
    {
        $text = mb_strtolower($text);

        if (in_array($text, $this->skip, true)) {
            return null;
        }

        foreach ($this->texts as $text_musician) {
            if (str_contains($text, $text_musician)) {
                return true;
            }
        }

        return null;
    }
}
