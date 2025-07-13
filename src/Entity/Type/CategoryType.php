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
    public const PAREMIA = 82;
    public const FAIRY_TALE = 83;
    public const LULLABY = 84;
    public const RIDDLE = 85;
    public const PARABLE = 86;
    public const ABOUT_RECORD = 90;
    public const ABOUT_INFORMANT = 91;
    public const ABOUT_OTHER_INFORMANTS = 92;
    public const CHANGE_INFORMANTS = 93;
    public const STAGE_ACTION = 97;
    public const FILM = 98;
    public const OTHER = 99;

    public const TYPES = [
        self::KARAHOD => 'карагод',
        self::DANCE => 'танец',
        self::QUADRILLE => 'кадрыля',
        self::DANCE_GAME => 'танец-гульня',
        self::CHORUSES => 'прыпеўкі',
        self::MELODY => 'найгрыш',
        self::DANCE_MOVEMENTS => 'рухі танца',
        self::SONGS => 'песня',
        self::POEMS => 'верш',
        self::CEREMONY => 'абрад',
        self::GAME => 'гульня',
        self::STORY => 'аповед',
        self::ABOUT_DANCES => 'згадванне пра танцы',
        self::PAREMIA => 'паремія', // малыя жанры
        self::FAIRY_TALE => 'казка',
        self::LULLABY => 'калыханка',
        self::RIDDLE => 'загадка', // таксама малы жанр
        self::PARABLE => 'прыпавесць',
        self::ABOUT_RECORD => 'звесткі пра запіс',
        self::ABOUT_INFORMANT => 'звесткі пра інфармантаў',
        self::ABOUT_OTHER_INFORMANTS => 'звесткі пра іншых інфармантаў',
        self::CHANGE_INFORMANTS => 'змена інфармантаў',
        self::STAGE_ACTION => 'сцэнічная дзея',
        self::FILM => 'фільм',
        self::OTHER => 'іншае',
    ];

    public const TYPES_MANY = [
        self::KARAHOD => 'карагоды',
        self::DANCE => 'танцы',
        self::QUADRILLE => 'кадрылі',
        self::DANCE_GAME => 'танец-гульні',
        self::CHORUSES => null,
        self::MELODY => 'найгрышы',
        self::DANCE_MOVEMENTS => null,
        self::SONGS => 'песні',
        self::POEMS => 'вершы',
        self::CEREMONY => 'абрады',
        self::GAME => 'гульні',
        self::STORY => 'аповеды',
        self::ABOUT_DANCES => null,
        self::PAREMIA => 'пареміі', // малыя жанры
        self::FAIRY_TALE => 'казкі',
        self::LULLABY => 'калыханкі',
        self::RIDDLE => 'загадкі', // таксама малы жанр
        self::PARABLE => 'прыпавесці',
        self::ABOUT_RECORD => null,
        self::ABOUT_INFORMANT => null,
        self::ABOUT_OTHER_INFORMANTS => null,
        self::CHANGE_INFORMANTS => null,
        self::STAGE_ACTION => null,
        self::FILM => null,
        self::OTHER => null,
    ];

    private const VARIANTS_SAME = [
        self::DANCE_MOVEMENTS => ['рух танца', 'рухі танцаў'],
        self::QUADRILLE => ['кадрыль'],
        self::SONGS => ['песень', 'песьня', 'песьні'],
        self::STORY => ['аповеды пра', 'аповед пра', 'аповед'],
        self::ABOUT_DANCES => [
            'згадваньне пра танцы', 'як танцавалі', 'пра танец', 'каманда ў танцах', 'каманды ў танцах'
        ],
        self::FAIRY_TALE => ['казка пра'],
        self::PARABLE => ['прытча'],
        self::PAREMIA => [
            'прыкмета', 'прыкметы', 'праклён', 'вітанне', 'вітання', 'афарызм', 'прымаўка', 'прымаўкі',
            'пажаданне', 'пажаданні', 'каламбур', 'тост', 'лічылка', 'лічылкі', 'прыказка', 'прыказкі',
        ],
        self::ABOUT_RECORD => [
            'зьвесткі пра запіс',
            'звесткі пра перадачу',
        ],
        self::ABOUT_INFORMANT => [
            'звесткі пра інфарманта',
            'звесткі пра інфармантку',
            'звесткі пра інфармантак',
            'звесткі пра інфармантаў',
            'звесткі пра інфарматара',
            'звесткі пра гурт-інфармант',
            'звесткі пра гарманіста',
            'зьвесткі пра інфарманта',
            'зьвесткі пра інфарматара',
        ],
        self::ABOUT_OTHER_INFORMANTS => ['зьвесткі пра іншых інфармантаў'],
        self::CHANGE_INFORMANTS => ['змена інфарманта', 'зьмена інфарманта', 'зьмена інфармантаў'],
    ];

    private const VARIANTS_OTHER = [
        self::SONGS => [
            'сьпявае', 'напеў', 'пятроўская', 'валачобная',
            'сьпявалі', 'масьленіца', 'бяседная', 'масьленка', 'жніўная', 'любоўная', 'вясельная',
            'лірычная', 'салдацкая', 'талочная', 'балада', 'паставая', 'сямейна-бытавая', 'жартоўная',
            'вялікодная', 'піліпаўская', 'калядная', 'раманс', 'масьленыя', 'вясельныя', 'турэмная', 'рамансы',
            'веснавыя', 'лірычныя', 'на сене', 'провады ў армію', 'лірыка', 'хрэсьбінная'
        ],
    ];

    private const VARIANTS_SONG_CEREMONY = [
        'каляды', 'хрэсьбіны', 'жніво', 'вяселле', 'вясельле', 'купальле', 'юр\'я', 'дажынкі',
    ];

    public const TEXT_JOIN = [
        self::OTHER => ['цікавыя словы, дыялекты', 'дыялекты', 'цікавыя выразы'],
    ];

    public const SYSTEM_TYPES = [
        self::ABOUT_RECORD,
        self::ABOUT_INFORMANT,
        self::ABOUT_OTHER_INFORMANTS,
        self::CHANGE_INFORMANTS,
    ];

    public const TYPES_BY_TAGS = [
        self::KARAHOD => ['карагоды'],
        self::DANCE =>  ['танец'],
        self::QUADRILLE =>  ['кадрыля'],
        self::DANCE_GAME =>  ['танец-гульня'],
        self::CHORUSES => ['прыпеўкі'],
        self::MELODY =>  ['найгрыш'],
        self::DANCE_MOVEMENTS =>  ['рухі танца'],
        self::SONGS => [
            'лірычныя песні', 'балады', 'савецкая песня', 'жартоўная песня',
            'касецкая песня (сенакосная)', 'восеньская песня', 'талака і талочныя песні',
        ],
        self::POEMS =>  ['верш'],
        self::CEREMONY =>  ['абрад'],
        self::GAME => ['гульні'],
        self::STORY => [
            'размова', 'звычаі', 'апісанні розныя',
            'каляндарная абраднасць і паэзія', 'сямейная абраднасць і паэзія',
        ],
        self::ABOUT_DANCES =>  ['згадванне пра танцы'],
        self::PAREMIA => [
            'прыкметы', 'праклёны', 'вітання', 'афарызмы', 'прымаўкі',
            'пажаданні', 'каламбуры', 'тосты', 'лічылкі', 'прыказкі',
        ], // малыя жанры
        self::FAIRY_TALE => ['казка'],
        self::LULLABY => ['калыханкі'],
        self::RIDDLE => ['загадкі'],
        self::PARABLE =>  ['прыпавесць'],
        self::OTHER => [],
    ];

    public static function getSingleName(int $type): ?string
    {
        return isset(self::TYPES[$type]) ? mb_ucfirst(self::TYPES[$type]) : null;
    }

    public static function getManyOrSingleName(int $type): ?string
    {
        if (isset(self::TYPES_MANY[$type])) {
            return mb_ucfirst(self::TYPES_MANY[$type]);
        }

        return self::getSingleName($type);
    }

    public static function getSingleManyNames(): array
    {
        $types = [];
        foreach (self::TYPES as $key => $type) {
            $types[$key] = mb_ucfirst($type) . (null !== self::TYPES_MANY[$key] ? '/' . self::TYPES_MANY[$key] : '');
        }

        return $types;
    }

    public static function isTypeNextBlock(int $type): bool
    {
        return in_array($type, [self::ABOUT_RECORD, self::CHANGE_INFORMANTS], true);
    }

    public static function findId(string $text, string $textNext, bool $isAll = true): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::TYPES as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::TYPES_MANY as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::TYPES as $key => $name) {
            if (!$isAll && !in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])) {
                continue;
            }
            if (false !== mb_strstr($text, $name)) {
                return $key;
            }
        }

        foreach (self::VARIANTS_SAME as $key => $variants) {
            foreach ($variants as $variant) {
                if (
                    !$isAll
                    && !in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])
                ) {
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

        foreach (self::TYPES as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::TYPES_MANY as $key => $name) {
            if ($text === $name) {
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
        if (in_array(mb_strtolower($text), self::TEXT_JOIN[self::OTHER], true)) {
            return self::OTHER;
        }

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

    public static function getCategoryByTags(string $tag): ?int
    {
        $tag = mb_strtolower($tag);

        foreach (self::TYPES_BY_TAGS as $type => $tags) {
            if (in_array($tag, $tags, true)) {
                return $type;
            }
        }

        return null;
    }
}
