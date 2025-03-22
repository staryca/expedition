<?php

declare(strict_types=1);

namespace App\Entity\Type;

class CategoryType
{
    public const KARAHOD = 10;
    public const DANCE = 20;
    public const QUADRILLE = 21;
    public const DANCE_GAME = 25;
    public const CHORUSES = 26;
    public const MELODY = 27;
    public const DANCE_MOVEMENTS = 28;
    public const SONGS = 30;
    public const POEMS = 31;
    public const CEREMONY = 40;
    public const GAME = 50;
    public const STORY = 80;
    public const ABOUT_DANCES = 81;
    public const PROVERB = 82;
    public const FAIRY_TALE = 83;
    public const LULLABY = 84;
    public const ABOUT_RECORD = 90;
    public const ABOUT_INFORMANT = 91;
    public const ABOUT_OTHER_INFORMANTS = 92;
    public const STAGE_ACTION = 97;
    public const FILM = 98;
    public const OTHER = 99;

    public const TYPES = [
        self::KARAHOD => 'Карагод',
        self::DANCE => 'Танец',
        self::QUADRILLE => 'Кадрыля',
        self::DANCE_GAME => 'Танец-гульня',
        self::CHORUSES => 'Прыпеўкі',
        self::MELODY => 'Найгрыш',
        self::DANCE_MOVEMENTS => 'Рухі танца',
        self::SONGS => 'Песьня',
        self::POEMS => 'Вершы',
        self::CEREMONY => 'Абрад',
        self::GAME => 'Гульня',
        self::STORY => 'Аповеды',
        self::ABOUT_DANCES => 'Згадваньне пра танцы',
        self::PROVERB => 'Прыказка',
        self::FAIRY_TALE => 'Казка',
        self::LULLABY => 'Калыханкі',
        self::ABOUT_RECORD => 'Звесткі пра запіс',
        self::ABOUT_INFORMANT => 'Звесткі пра інфармантаў',
        self::ABOUT_OTHER_INFORMANTS => 'Звесткі пра іншых інфармантаў',
        self::STAGE_ACTION => 'Сцэнічная дзея',
        self::FILM => 'Фільм',
        self::OTHER => 'Іншае',
    ];

    private const VARIANTS_SAME = [
        self::DANCE_MOVEMENTS => ['рух танца', 'рухі танцаў'],

        self::DANCE => ['танцы'],
        self::QUADRILLE => ['кадрыль'],
        self::CHORUSES => ['прыпеўка'],
        self::MELODY => ['найгрыш'],
        self::SONGS => ['песень', 'песня', 'песні', 'песьні'],
        self::POEMS => ['верш'],
        self::CEREMONY => ['абрады'],
        self::GAME => ['гульні'],
        self::STORY => ['аповеды пра', 'аповед пра', 'аповед'],
        self::ABOUT_DANCES => ['згадванне пра танцы'],
        self::PROVERB => ['прыказкі'],
        self::FAIRY_TALE => ['казка пра'],
        self::LULLABY => ['калыханка'],
        self::ABOUT_RECORD => ['зьвесткі пра запіс'],
        self::ABOUT_INFORMANT => [
            'зьвесткі пра інфарманта',
            'звесткі пра інфарманта',
            'зьвесткі пра інфарматара',
            'звесткі пра інфарматара',
        ],
        self::ABOUT_OTHER_INFORMANTS => ['зьвесткі пра іншых інфармантаў'],
    ];

    private const VARIANTS_OTHER = [
        self::DANCE_GAME => ['танец-гульня'],

        self::SONGS => [
            'сьпявае', 'напеў', 'пятроўская', 'валачобная',
            'сьпявалі', 'масьленіца', 'бяседная', 'масьленка', 'жніўная', 'любоўная', 'вясельная',
            'лірычная', 'салдацкая', 'талочная', 'балада', 'паставая', 'сямейна-бытавая', 'жартоўная',
            'вялікодная', 'піліпаўская', 'калядная', 'раманс', 'масьленыя', 'вясельныя', 'турэмная', 'рамансы',
            'веснавыя', 'лірычныя', 'на сене', 'провады ў армію', 'лірыка',
        ],
    ];

    private const VARIANTS_SONG_CEREMONY = [
        'каляды', 'хрэсьбіны', 'жніво', 'вяселле', 'вясельле', 'купальле', 'юр\'я', 'дажынкі',
    ];

    public const TEXT_JOIN = [
        self::OTHER => ['Цікавыя словы, дыялекты', 'Дыялекты', 'Цікавыя выразы'],
    ];

    public static function findId(string $text, string $textNext, bool $isAll = true): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::TYPES as $key => $name) {
            if (!$isAll && !in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])) {
                continue;
            }
            if (false !== mb_strstr($text, mb_strtolower($name))) {
                return $key;
            }
        }

        foreach (self::VARIANTS_SAME as $key => $variants) {
            foreach ($variants as $variant) {
                if (!$isAll && !in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])) {
                    continue;
                }
                if (false !== mb_strstr($text, $variant)) {
                    return $key;
                }
            }
        }

        return self::findOtherVariantsId($text, $textNext);
    }

    public static function findOtherVariantsId(string $text, string $textNext): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::VARIANTS_OTHER as $key => $variants) {
            foreach ($variants as $variant) {
                if (false !== mb_strstr($text, $variant)) {
                    return $key;
                }
            }
        }

        foreach (self::VARIANTS_SONG_CEREMONY as $variant) {
            if (false !== mb_strstr($text, $variant)) {
                $char = mb_substr($textNext, 0, 1);

                return in_array($char, ['"', '']) ? self::SONGS : self::CEREMONY;
            }
        }

        return null;
    }

    public static function getId(string $text, string $textNext): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::TEXT_JOIN[self::OTHER] as $name) {
            if (mb_strtolower($text) === mb_strtolower($name)) {
                return self::OTHER;
            }
        }

        foreach (self::TYPES as $key => $name) {
            if ($text === mb_strtolower($name)) {
                return $key;
            }
        }

        foreach (self::VARIANTS_SAME as $key => $variants) {
            if (in_array($text, $variants, true)) {
                return $key;
            }
        }

        return self::getIdForOther($text, $textNext);
    }

    public static function getIdForOther(string $text, string $textNext): ?int
    {
        foreach (self::VARIANTS_OTHER as $key => $variants) {
            if (in_array($text, $variants, true)) {
                return $key;
            }
        }

        if (in_array($text, self::VARIANTS_SONG_CEREMONY, true)) {
            $char = mb_substr($textNext, 0, 1);

            return in_array($char, ['"', '']) ? self::SONGS : self::CEREMONY;
        }

        return null;
    }
}
