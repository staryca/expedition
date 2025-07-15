<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class Month
{
    public const JANUARY = 1;
    public const FEBRUARY = 2;
    public const MARCH = 3;
    public const APRIL = 4;
    public const MAY = 5;
    public const JUNE = 6;
    public const JULY = 7;
    public const AUGUST = 8;
    public const SEPTEMBER = 9;
    public const OCTOBER = 10;
    public const NOVEMBER = 11;
    public const DECEMBER = 12;

    private const VARIANTS = [
        self::JANUARY => ['студзень', 'студзеня'],
        self::FEBRUARY => ['люты', 'лютага'],
        self::MARCH => ['сакавік', 'сакавіка'],
        self::APRIL => ['красавік', 'красавіка'],
        self::MAY => ['май', 'мая', 'травень', 'траўня'],
        self::JUNE => ['чэрвень', 'чэрвеня'],
        self::JULY => ['ліпень', 'ліпеня'],
        self::AUGUST => ['жнівень', 'жніўня'],
        self::SEPTEMBER => ['верасень', 'верасня'],
        self::OCTOBER => ['кастрычнік', 'кастрычніка'],
        self::NOVEMBER => ['лістапад', 'лістапада'],
        self::DECEMBER => ['снежань', 'снежня'],
    ];

    public static function getMonth(string &$text): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::VARIANTS as $month => $values) {
            foreach ($values as $value) {
                if (str_starts_with($text, $value)) {
                    $text = mb_substr($text, mb_strlen($value));
                    return $month;
                }
            }
        }

        return null;
    }
}
