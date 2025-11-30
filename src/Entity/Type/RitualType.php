<?php

declare(strict_types=1);

namespace App\Entity\Type;

class RitualType
{
    public const SEASON_WINTER = 'Зіма';
    public const SEASON_SPRING = 'Вясна';
    public const SEASON_SUMMER = 'Лета';
    public const SEASON_AUTUMN = 'Восень';

    public const CEREMONY_PILIPOVKA = 'Піліпаўка';
    public const CEREMONY_CRISTMAS = 'Каляды';
    public const CEREMONY_CIARESZKA = 'Жаніцьба Цярэшкі';
    public const CEREMONY_MASLENICA = 'Масленіца';
    public const CEREMONY_JURJA = 'Юр’я';
    public const CEREMONY_TRINITY = 'Тройца';
    public const CEREMONY_KUPALLE = 'Купалле';
    public const CEREMONY_PIOTR = 'Пятрок';
    public const CEREMONY_HARVEST = 'Жніво';

    private const SEASONS = [
        self::SEASON_WINTER => ['зімовая'],
        self::SEASON_SPRING => ['веснавая', 'веснавыя'],
        self::SEASON_SUMMER => ['летняя'],
        self::SEASON_AUTUMN => ['восеньская'],
    ];

    private const CEREMONIES = [
        self::CEREMONY_PILIPOVKA => ['піліпаўская'],
        self::CEREMONY_CRISTMAS => ['калядная'],
        self::CEREMONY_CIARESZKA => ['цярэшкі', 'цярэшка'],
        self::CEREMONY_MASLENICA => ['масьленка', 'масьленыя', 'масьленіца', 'масленкавая'],
        self::CEREMONY_JURJA => ['юр\'еўская'],
        self::CEREMONY_TRINITY => ['траецкая'],
        self::CEREMONY_KUPALLE => ['купальская'],
        self::CEREMONY_PIOTR => ['пятроўская'],
        self::CEREMONY_HARVEST => ['жніўная'],
    ];

    public static function detectRitual(string $text): ?string
    {
        $text = mb_strtolower($text);

        foreach (self::CEREMONIES as $ceremony => $words) {
            if (mb_strpos($text, mb_strtolower($ceremony)) !== false) {
                return $ceremony;
            }

            foreach ($words as $word) {
                if (mb_strpos($text, $word) !== false) {
                    return $ceremony;
                }
            }
        }

        foreach (self::SEASONS as $season => $words) {
            if (mb_strpos($text, mb_strtolower($season)) !== false) {
                return $season;
            }

            foreach ($words as $word) {
                if (mb_strpos($text, $word) !== false) {
                    return $season;
                }
            }
        }

        return null;
    }
}
